(() => {
    'use strict';

    const root = document.documentElement;
    const themeButton = document.getElementById('themeToggle');
    const languageButton = document.getElementById('languageToggle');
    const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const storedTheme = localStorage.getItem('lf-theme');
    const storedLanguage = localStorage.getItem('lf-language') || 'en';
    const sidebar = document.getElementById('appSidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');

    document.querySelectorAll('link[rel="stylesheet"][href*="assets/css/style.css"]').forEach(link => {
        const stylesheetUrl = new URL(link.href, window.location.origin);
        if (stylesheetUrl.searchParams.get('v') !== '20261002-2') {
            stylesheetUrl.searchParams.set('v', '20261002-2');
            link.href = stylesheetUrl.toString();
        }
    });

    if (sidebar) {
        document.body.classList.add('has-sidebar');
        const currentPath = window.location.pathname.replace(/\/$/, '');
        document.querySelectorAll('.sidebar-link').forEach(link => {
            const linkPath = new URL(link.href, window.location.origin).pathname.replace(/\/$/, '');
            if (linkPath === currentPath) link.classList.add('active');
        });
        const closeSidebar = () => document.body.classList.remove('sidebar-open');
        sidebarToggle?.addEventListener('click', () => document.body.classList.toggle('sidebar-open'));
        sidebarBackdrop?.addEventListener('click', closeSidebar);
        sidebar.querySelectorAll('a').forEach(link => link.addEventListener('click', closeSidebar));
        window.addEventListener('keydown', event => {
            if (event.key === 'Escape') closeSidebar();
        });
    }

    const translations = {
        'Liwa University Lost & Found': 'جامعة ليوا للمفقودات والموجودات',
        'Lost & Found': 'المفقودات والموجودات',
        'Search Items': 'البحث عن الأغراض',
        'Report Lost Item': 'الإبلاغ عن غرض مفقود',
        'Report Found Item': 'تسجيل غرض موجود',
        'Create Account': 'إنشاء حساب',
        'Create account': 'إنشاء حساب',
        'Login': 'تسجيل الدخول',
        'Logout': 'تسجيل الخروج',
        'Dashboard': 'لوحة التحكم',
        'My Reports': 'بلاغاتي',
        'Notifications': 'الإشعارات',
        'Profile': 'الملف الشخصي',
        'Find what you lost.': 'اعثر على ما فقدته.',
        'Return what you found.': 'أعِد ما عثرت عليه.',
        'Find what you lost.\nReturn what you found.': 'اعثر على ما فقدته.\nأعِد ما عثرت عليه.',
        'A secure university platform for reporting, matching, verifying, and returning lost items to their owners.': 'منصة جامعية آمنة للإبلاغ والمطابقة والتحقق وإعادة الأغراض المفقودة إلى أصحابها.',
        'Digital Lost-and-Found Management System': 'النظام الرقمي لإدارة المفقودات والموجودات',
        'System Ready': 'النظام جاهز',
        'Database connected': 'قاعدة البيانات متصلة',
        'Database connection failed': 'فشل الاتصال بقاعدة البيانات',
        'Submit a Report': 'تقديم بلاغ',
        'Search & Match': 'البحث والمطابقة',
        'Verify & Return': 'التحقق والتسليم',
        'Welcome back': 'مرحبًا بعودتك',
        'Student registration': 'تسجيل طالب',
        'Create your account': 'أنشئ حسابك',
        'Full name': 'الاسم الكامل',
        'Student ID': 'الرقم الجامعي',
        'Email address': 'البريد الإلكتروني',
        'Phone number': 'رقم الهاتف',
        'Password': 'كلمة المرور',
        'Confirm password': 'تأكيد كلمة المرور',
        'Submit Registration': 'إرسال طلب التسجيل',
        'Already registered? Login': 'لديك حساب؟ سجل الدخول',
        'Welcome': 'مرحبًا',
        'Student dashboard': 'لوحة الطالب',
        'Staff dashboard': 'لوحة الموظف',
        'Admin dashboard': 'لوحة المسؤول',
        'Your account is active. Choose an action to begin.': 'حسابك مفعل. اختر إجراءً للبدء.',
        'Possible Matches': 'التطابقات المحتملة',
        'What did you lose?': 'ما الغرض الذي فقدته؟',
        'What did you find?': 'ما الغرض الذي عثرت عليه؟',
        'Item name': 'اسم الغرض',
        'Category': 'التصنيف',
        'Building': 'المبنى',
        'Specific location': 'الموقع التفصيلي',
        'Date lost': 'تاريخ الفقدان',
        'Date found': 'تاريخ العثور',
        'Photo (optional)': 'صورة (اختيارية)',
        'Description': 'الوصف',
        'Storage location (optional)': 'مكان التخزين (اختياري)',
        'Submit Lost Item Report': 'إرسال بلاغ المفقود',
        'Submit Found Item Report': 'إرسال بلاغ الموجود',
        'Search current lost and found reports using any combination of filters.': 'ابحث في بلاغات المفقودات والموجودات باستخدام أي مجموعة من عوامل التصفية.',
        'Keyword': 'كلمة البحث',
        'Report type': 'نوع البلاغ',
        'All reports': 'جميع البلاغات',
        'Lost items': 'المفقودات',
        'Found items': 'الموجودات',
        'All categories': 'جميع التصنيفات',
        'All buildings': 'جميع المباني',
        'Search': 'بحث',
        'Clear': 'مسح',
        'View details →': 'عرض التفاصيل ←',
        'Status tracking': 'متابعة الحالة',
        'Lost Item Reports': 'بلاغات المفقودات',
        'Found Item Reports': 'بلاغات الموجودات',
        'New Lost Report': 'بلاغ مفقود جديد',
        'New Found Report': 'بلاغ موجود جديد',
        'Submitted': 'تم الإرسال',
        'Under Review': 'قيد المراجعة',
        'Available': 'متاح',
        'Matched': 'تمت المطابقة',
        'Ready For Collection': 'جاهز للاستلام',
        'Returned': 'تم التسليم',
        'Closed': 'مغلق',
        'Rejected': 'مرفوض',
        'Claim Pending': 'الطلب قيد المراجعة',
        'Submit Claim': 'إرسال طلب الاستلام',
        'Proof of ownership': 'إثبات الملكية',
        'Account settings': 'إعدادات الحساب',
        'My Profile': 'ملفي الشخصي',
        'Personal information': 'المعلومات الشخصية',
        'Save Profile': 'حفظ البيانات',
        'Change password': 'تغيير كلمة المرور',
        'Current password': 'كلمة المرور الحالية',
        'New password': 'كلمة المرور الجديدة',
        'Confirm new password': 'تأكيد كلمة المرور الجديدة',
        'Save Changes': 'حفظ التعديلات',
        'Cancel Report': 'إلغاء البلاغ',
        'Edit or Cancel Report': 'تعديل أو إلغاء البلاغ',
        'Back': 'رجوع',
        'Registration Requests': 'طلبات التسجيل',
        'Create Staff': 'إنشاء موظف',
        'Create Staff Account': 'إنشاء حساب موظف',
        'Manage Users': 'إدارة المستخدمين',
        'Users': 'المستخدمون',
        'System Data': 'بيانات النظام',
        'Departments': 'الأقسام',
        'Analytics': 'الإحصائيات',
        'Reports & Analytics': 'التقارير والإحصائيات',
        'Reports': 'البلاغات',
        'Claims': 'طلبات الاستلام',
        'Returns': 'عمليات التسليم',
        'Inventory': 'المخزون',
        'Review Reports': 'مراجعة البلاغات',
        'Claim Requests': 'طلبات الاستلام',
        'Found Item Inventory': 'مخزون الأغراض الموجودة',
        'Returns & Case Closure': 'التسليم وإغلاق الحالات',
        'Approve': 'قبول',
        'Reject': 'رفض',
        'Save': 'حفظ',
        'Update': 'تحديث',
        'Add Building': 'إضافة مبنى',
        'Add Category': 'إضافة تصنيف',
        'Add Department': 'إضافة قسم',
        'Department name': 'اسم القسم',
        'Building name': 'اسم المبنى',
        'Category name': 'اسم التصنيف',
        'Assign': 'ربط',
        'Mark all as read': 'تحديد الكل كمقروء',
        'No photo': 'لا توجد صورة',
        'No reports match your search.': 'لا توجد بلاغات مطابقة لبحثك.',
        'Page not found': 'الصفحة غير موجودة',
        'Something went wrong': 'حدث خطأ ما',
        'Return Home': 'العودة للرئيسية',
        'Return to Dashboard': 'العودة إلى لوحة التحكم'
    };

    const textOriginals = new WeakMap();
    const attributeOriginals = new WeakMap();

    function updateTheme(theme) {
        root.dataset.theme = theme;
        localStorage.setItem('lf-theme', theme);
        if (themeButton) {
            themeButton.querySelector('span').textContent = theme === 'dark' ? '☀' : '☾';
            themeButton.setAttribute('aria-label', theme === 'dark' ? 'Use light mode' : 'Use dark mode');
        }
    }

    function translateText(language) {
        root.lang = language;
        root.dir = language === 'ar' ? 'rtl' : 'ltr';
        document.body.classList.toggle('arabic-ui', language === 'ar');

        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
            acceptNode(node) {
                const parent = node.parentElement;
                if (!parent || ['SCRIPT', 'STYLE', 'TEXTAREA'].includes(parent.tagName)) return NodeFilter.FILTER_REJECT;
                return node.nodeValue.trim() ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
            }
        });

        const nodes = [];
        while (walker.nextNode()) nodes.push(walker.currentNode);
        nodes.forEach(node => {
            if (!textOriginals.has(node)) textOriginals.set(node, node.nodeValue);
            const original = textOriginals.get(node);
            if (language === 'en') {
                node.nodeValue = original;
                return;
            }
            const trimmed = original.trim();
            let translated = translations[trimmed];
            if (!translated && trimmed.startsWith('Welcome, ')) translated = `مرحبًا، ${trimmed.slice(9)}`;
            if (!translated && /^Notifications \(\d+\)$/.test(trimmed)) translated = `الإشعارات (${trimmed.match(/\d+/)[0]})`;
            if (!translated && /^\d+ results?$/.test(trimmed)) translated = trimmed.replace('result', 'نتيجة').replace('s', '');
            if (!translated && /^Lost #\d+$/.test(trimmed)) translated = trimmed.replace('Lost', 'مفقود');
            if (!translated && /^Found #\d+$/.test(trimmed)) translated = trimmed.replace('Found', 'موجود');
            if (!translated && trimmed.startsWith('Lost on ')) translated = `فُقد بتاريخ ${trimmed.slice(8)}`;
            if (!translated && trimmed.startsWith('Found on ')) translated = `عُثر عليه بتاريخ ${trimmed.slice(9)}`;
            if (translated) node.nodeValue = original.replace(trimmed, translated);
        });

        document.querySelectorAll('[placeholder], [title], [aria-label]').forEach(element => {
            if (!attributeOriginals.has(element)) {
                attributeOriginals.set(element, {
                    placeholder: element.getAttribute('placeholder'),
                    title: element.getAttribute('title'),
                    ariaLabel: element.getAttribute('aria-label')
                });
            }
            const originals = attributeOriginals.get(element);
            ['placeholder', 'title', 'aria-label'].forEach(attribute => {
                const key = attribute === 'aria-label' ? 'ariaLabel' : attribute;
                const original = originals[key];
                if (!original) return;
                element.setAttribute(attribute, language === 'ar' && translations[original] ? translations[original] : original);
            });
        });

        if (languageButton) languageButton.textContent = language === 'ar' ? 'English' : 'العربية';
        localStorage.setItem('lf-language', language);
    }

    updateTheme(storedTheme || (systemDark ? 'dark' : 'light'));
    translateText(storedLanguage);

    themeButton?.addEventListener('click', () => {
        updateTheme(root.dataset.theme === 'dark' ? 'light' : 'dark');
    });
    languageButton?.addEventListener('click', () => {
        translateText(root.lang === 'ar' ? 'en' : 'ar');
    });

    document.querySelectorAll('.button, button').forEach(button => {
        button.addEventListener('pointerdown', event => {
            const ripple = document.createElement('span');
            const rect = button.getBoundingClientRect();
            ripple.className = 'button-ripple';
            ripple.style.left = `${event.clientX - rect.left}px`;
            ripple.style.top = `${event.clientY - rect.top}px`;
            button.appendChild(ripple);
            setTimeout(() => ripple.remove(), 650);
        });
    });

    const animatedElements = document.querySelectorAll('.report-card, .dashboard-card, .feature, .features article, .metric-grid article, .analytics-card, .management-card, .auth-card, .item-form-card, .detail-card, .table-wrap, .match-card, .claim-review-card');
    if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('reveal-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08 });
        animatedElements.forEach((element, index) => {
            element.classList.add('reveal-item');
            element.style.setProperty('--reveal-delay', `${Math.min(index * 45, 300)}ms`);
            observer.observe(element);
        });
    }

    document.body.classList.add('page-ready');
})();
