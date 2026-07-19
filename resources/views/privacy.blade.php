<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Privacy Policy - iCard Maker</title>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-sans bg-gray-50 text-gray-800 dark:bg-slate-900 dark:text-gray-100 min-h-screen flex flex-col">
        <!-- Navigation -->
        <header class="bg-white dark:bg-slate-800 shadow-sm border-b border-gray-100 dark:border-slate-700 py-4">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                <a href="/" class="flex items-center space-x-2">
                    <img src="{{ asset('images/logo.png') }}" class="h-8 w-auto" alt="iCard Logo">
                    <span class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">iCard Maker</span>
                </a>
                <a href="/" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                    &larr; Back to Home
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-grow py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-700 p-8 sm:p-12">
                <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white mb-6">Privacy Policy</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">Last Updated: {{ date('F d, Y') }}</p>

                <div class="space-y-6 text-gray-600 dark:text-gray-300 leading-relaxed">
                    <p>
                        At <strong>iCard Maker</strong>, we are committed to protecting the privacy of school administrators, teachers, parents, and students. This Privacy Policy describes how we collect, use, and safeguard personal information when using our application and website.
                    </p>

                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mt-8">1. Information We Collect</h2>
                    <p>
                        We collect personal and organization details to facilitate digital student ID card creation and verification:
                    </p>
                    <ul class="list-disc pl-5 space-y-2">
                        <li><strong>User Details</strong>: Name, email address, mobile number, and passwords for account creation and login.</li>
                        <li><strong>School/Institute Details</strong>: School name, address, and logos upload to customize card templates.</li>
                        <li><strong>Student Profiles</strong>: First name, middle name, last name, date of birth, blood group, contact number, address, and profile photo.</li>
                    </ul>

                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mt-8">2. Camera & Photo Permissions</h2>
                    <p>
                        Our mobile app requires access to your device's <strong>Camera</strong> and <strong>Photo Gallery</strong> to capture and upload profile pictures of student candidates. These images are processed locally and securely uploaded to our servers solely for rendering digital ID cards. We do not use or share these photos for any other purpose.
                    </p>

                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mt-8">3. How We Use Information</h2>
                    <p>
                        The collected information is used solely to:
                    </p>
                    <ul class="list-disc pl-5 space-y-2">
                        <li>Authorize access and manage institute user roles (Admins, Teachers, Parents).</li>
                        <li>Generate digital student ID cards with customized school headers and barcodes.</li>
                        <li>Send pending invitations to school members via system notification frameworks.</li>
                    </ul>

                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mt-8">4. Data Protection & Storage</h2>
                    <p>
                        All user and student data is stored securely using industry-standard encryption protocols. Database access is restricted to authorized personnel. Student data remains under the ownership and control of the respective school administrators.
                    </p>

                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mt-8">5. Third-Party Sharing</h2>
                    <p>
                        We do not sell, lease, or share any personal student or school data with third-party advertisers or commercial entities.
                    </p>

                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mt-8">6. Contact Us</h2>
                    <p>
                        If you have any questions or feedback regarding our privacy practices, please contact us at:
                    </p>
                    <p class="font-semibold text-indigo-600 dark:text-indigo-400 mt-2">
                        Email: <a href="mailto:leenaitsolutions@gmail.com" class="hover:underline">leenaitsolutions@gmail.com</a>
                    </p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-gray-100 dark:bg-slate-900 border-t border-gray-200 dark:border-slate-800 py-8 text-center text-xs text-gray-500 dark:text-gray-400">
            <div class="max-w-7xl mx-auto px-4">
                <p>&copy; {{ date('Y') }} iCard Maker. All rights reserved.</p>
                <p class="mt-2">Created by Leena IT Solutions.</p>
            </div>
        </footer>
    </body>
</html>
