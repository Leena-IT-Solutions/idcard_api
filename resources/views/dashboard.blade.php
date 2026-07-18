<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Welcome Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow sm:rounded-3xl">
                <div class="p-6 text-gray-900 dark:text-gray-100 font-medium">
                    {{ __("Welcome back, ") . auth()->user()->name . "!" }}
                </div>
            </div>

            <!-- Git Self Updater Widget -->
            @if(auth()->user()->hasAnyRole(['saas_admin', 'school_admin']))
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow sm:rounded-3xl"
                 x-data="{
                    branch: 'Loading...',
                    commit: 'Loading...',
                    status: 'Loading...',
                    log: '',
                    loading: true,
                    updating: false,
                    init() {
                        this.fetchInfo();
                    },
                    fetchInfo() {
                        this.loading = true;
                        fetch('/git-info')
                            .then(res => res.json())
                            .then(data => {
                                this.branch = data.branch;
                                this.commit = data.commit;
                                this.status = data.status;
                                this.loading = false;
                            })
                            .catch(err => {
                                this.status = 'Error loading info';
                                this.loading = false;
                            });
                    },
                    runUpdate() {
                        if(this.updating) return;
                        this.updating = true;
                        this.log = 'Starting update...\n';
                        fetch('/git-update', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            this.log += data.log + '\n\n' + data.message;
                            this.updating = false;
                            this.fetchInfo();
                        })
                        .catch(err => {
                            this.log += '\nError running update.';
                            this.updating = false;
                        });
                    }
                 }">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">System Updater</h3>
                        <button @click="runUpdate()" :disabled="updating" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition disabled:opacity-50">
                            <span x-show="!updating">Update System</span>
                            <span x-show="updating">Updating...</span>
                        </button>
                    </div>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                            <span class="block text-gray-500 dark:text-gray-400 font-medium mb-1">Current Branch</span>
                            <span class="font-mono text-gray-900 dark:text-gray-100" x-text="branch"></span>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                            <span class="block text-gray-500 dark:text-gray-400 font-medium mb-1">Latest Commit</span>
                            <span class="font-mono text-gray-900 dark:text-gray-100" x-text="commit"></span>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                            <span class="block text-gray-500 dark:text-gray-400 font-medium mb-1">Working Tree</span>
                            <span class="font-mono text-gray-900 dark:text-gray-100" x-text="status"></span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-900 p-4" x-show="log.length > 0">
                    <pre class="text-green-400 font-mono text-xs whitespace-pre-wrap break-all" x-text="log"></pre>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
