import Cropper from 'cropperjs';
import { removeBackground } from '@imgly/background-removal';

export function photoStudio() {
    return {
        isOpen: false,
        step: 'crop', // 'crop' | 'background' | 'touchup' | 'preview'
        originalFile: null,
        cropper: null,
        aspectRatio: 1, // 1 for 1:1, 0.75 for 3:4
        rotation: 0,
        flipH: false,
        
        // Background removal & color
        isProcessingBg: false,
        bgErrorMessage: null,
        bgRemovedBlob: null,
        bgColor: '#ffffff',
        
        // Touch-up filters
        brightness: 100,
        contrast: 100,
        saturation: 100,
        sharpness: 0,
        
        // Warnings / Info
        resWarning: null,

        initStudio() {
            // Watch step switches to re-draw canvas if needed
            this.$watch('step', (newStep) => {
                if (newStep === 'touchup' || newStep === 'preview') {
                    this.renderCompositedCanvas();
                }
            });
        },

        openStudio(event) {
            const files = event.target.files;
            if (!files || !files.length) return;
            
            const file = files[0];
            this.originalFile = file;
            this.resetState();
            this.isOpen = true;

            const reader = new FileReader();
            reader.onload = (e) => {
                this.$nextTick(() => {
                    const img = this.$refs.cropImage;
                    if (!img) return;
                    img.onload = () => {
                        this.initCropper(img);
                        this.checkResolution(img);
                    };
                    img.src = e.target.result;
                });
            };
            reader.readAsDataURL(file);

            this.warmupEngine();
        },

        openStudioWithUrl(imageUrl) {
            if (!imageUrl) return;
            this.resetState();
            this.isOpen = true;

            this.$nextTick(() => {
                const img = this.$refs.cropImage;
                if (!img) return;
                img.crossOrigin = 'anonymous';
                img.onload = () => {
                    this.initCropper(img);
                    this.checkResolution(img);
                };
                img.src = imageUrl;
            });

            this.warmupEngine();
        },

        warmupEngine() {
            // Pre-warm WASM background removal engine in background
            try {
                if (window.crossOriginIsolated || true) {
                    removeBackground('/models/bg-removal/resources.json', {
                        publicPath: '/models/bg-removal/',
                        fetchArgs: { mode: 'no-cors' }
                    }).catch(() => {});
                }
            } catch (e) {
                // Ignore warmup failure
            }
        },

        resetState() {
            if (this.cropper) {
                this.cropper.destroy();
                this.cropper = null;
            }
            this.step = 'crop';
            this.aspectRatio = 1;
            this.rotation = 0;
            this.flipH = false;
            this.isProcessingBg = false;
            this.bgErrorMessage = null;
            this.bgRemovedBlob = null;
            this.bgColor = '#ffffff';
            this.brightness = 100;
            this.contrast = 100;
            this.saturation = 100;
            this.sharpness = 0;
            this.resWarning = null;
        },

        closeStudio() {
            if (this.cropper) {
                this.cropper.destroy();
                this.cropper = null;
            }
            this.isOpen = false;
            // Clear input
            const input = document.getElementById('photo-studio-input');
            if (input) input.value = '';
        },

        initCropper(imgElement) {
            if (this.cropper) {
                this.cropper.destroy();
                this.cropper = null;
            }

            this.cropper = new Cropper(imgElement, {
                aspectRatio: this.aspectRatio,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.85,
                responsive: true,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
                ready: () => {
                    this.renderCompositedCanvas();
                }
            });
        },

        setAspectRatio(ratio) {
            this.aspectRatio = ratio;
            if (this.cropper) {
                this.cropper.setAspectRatio(ratio);
            }
        },

        rotate(deg) {
            this.rotation = (this.rotation + deg) % 360;
            if (this.cropper) {
                this.cropper.rotate(deg);
            }
        },

        flipHorizontal() {
            this.flipH = !this.flipH;
            if (this.cropper) {
                this.cropper.scaleX(this.flipH ? -1 : 1);
            }
        },

        checkResolution(imgElement) {
            const w = imgElement.naturalWidth || 0;
            const h = imgElement.naturalHeight || 0;
            const minSide = Math.min(w, h);
            if (minSide > 0 && minSide < 400) {
                this.resWarning = `Photo resolution is low (${w}x${h}px). Print may look slightly soft.`;
            } else {
                this.resWarning = null;
            }
        },

        async removeBg() {
            if (!this.cropper) return;
            this.isProcessingBg = true;
            this.bgErrorMessage = null;

            try {
                const croppedCanvas = this.cropper.getCroppedCanvas({
                    maxWidth: 1200,
                    maxHeight: 1600,
                    fillColor: 'transparent',
                });

                const blob = await new Promise((resolve) => croppedCanvas.toBlob(resolve, 'image/png'));
                const imageSrc = URL.createObjectURL(blob);

                const resultBlob = await removeBackground(imageSrc, {
                    publicPath: '/models/bg-removal/',
                    progress: (key, current, total) => {
                        // Progress callback if needed
                    }
                });

                this.bgRemovedBlob = resultBlob;
                URL.revokeObjectURL(imageSrc);
            } catch (err) {
                console.error('Background removal failed:', err);
                this.bgErrorMessage = 'Background removal is unavailable on this device browser. You can still crop and adjust the photo.';
            } finally {
                this.isProcessingBg = false;
                this.renderCompositedCanvas();
            }
        },

        renderCompositedCanvas() {
            if (!this.cropper) return;

            const croppedCanvas = this.cropper.getCroppedCanvas({
                maxWidth: 1200,
                maxHeight: 1600,
            });

            const targetCanvas = this.$refs.studioCanvas;
            if (!targetCanvas) return;

            const ctx = targetCanvas.getContext('2d');
            targetCanvas.width = croppedCanvas.width;
            targetCanvas.height = croppedCanvas.height;

            // Clear
            ctx.clearRect(0, 0, targetCanvas.width, targetCanvas.height);

            // 1. Draw solid background color
            if (this.bgColor) {
                ctx.fillStyle = this.bgColor;
                ctx.fillRect(0, 0, targetCanvas.width, targetCanvas.height);
            }

            // Function to apply filters and draw image
            const drawSubject = (img) => {
                ctx.save();
                ctx.filter = `brightness(${this.brightness}%) contrast(${this.contrast}%) saturate(${this.saturation}%)`;
                ctx.drawImage(img, 0, 0, targetCanvas.width, targetCanvas.height);
                ctx.restore();
            };

            // 2. Draw Subject (either bg-removed or original cropped)
            if (this.bgRemovedBlob) {
                const img = new Image();
                img.onload = () => drawSubject(img);
                img.src = URL.createObjectURL(this.bgRemovedBlob);
            } else {
                drawSubject(croppedCanvas);
            }
        },

        applyPreset(preset) {
            if (preset === 'enhance') {
                this.brightness = 105;
                this.contrast = 110;
                this.saturation = 108;
            } else if (preset === 'studio') {
                this.brightness = 108;
                this.contrast = 105;
                this.saturation = 100;
            } else if (preset === 'reset') {
                this.brightness = 100;
                this.contrast = 100;
                this.saturation = 100;
            }
            this.renderCompositedCanvas();
        },

        async savePhoto() {
            if (!this.cropper) {
                alert('No photo has been loaded.');
                return;
            }

            try {
                const blob = await this.getFinalBlob();
                if (!blob) {
                    alert('Could not generate cropped photo. Please try again.');
                    return;
                }

                const file = new File([blob], 'student_photo.png', { type: 'image/png' });

                // Call Livewire's JS upload API to set $photo
                this.$wire.upload('photo', file, 
                    () => {
                        this.closeStudio();
                    },
                    (error) => {
                        alert('Photo upload failed: ' + error);
                    }
                );
            } catch (err) {
                console.error('Save photo error:', err);
                alert('Error processing photo: ' + (err.message || err));
            }
        },

        async getFinalBlob() {
            if (!this.cropper) return null;

            const croppedCanvas = this.cropper.getCroppedCanvas({
                maxWidth: 1200,
                maxHeight: 1600,
            });
            if (!croppedCanvas) return null;

            const targetCanvas = document.createElement('canvas');
            targetCanvas.width = croppedCanvas.width;
            targetCanvas.height = croppedCanvas.height;
            const ctx = targetCanvas.getContext('2d');

            // 1. Draw solid background color
            if (this.bgColor) {
                ctx.fillStyle = this.bgColor;
                ctx.fillRect(0, 0, targetCanvas.width, targetCanvas.height);
            }

            // Helper to apply filters and draw
            const drawSubject = (img) => {
                ctx.save();
                ctx.filter = `brightness(${this.brightness}%) contrast(${this.contrast}%) saturate(${this.saturation}%)`;
                ctx.drawImage(img, 0, 0, targetCanvas.width, targetCanvas.height);
                ctx.restore();
            };

            // 2. Draw Subject
            if (this.bgRemovedBlob) {
                await new Promise((resolve) => {
                    const img = new Image();
                    img.onload = () => {
                        drawSubject(img);
                        resolve();
                    };
                    img.onerror = () => {
                        drawSubject(croppedCanvas);
                        resolve();
                    };
                    img.src = URL.createObjectURL(this.bgRemovedBlob);
                });
            } else {
                drawSubject(croppedCanvas);
            }

            return new Promise((resolve) => {
                targetCanvas.toBlob(resolve, 'image/png', 0.95);
            });
        }
    };
}
