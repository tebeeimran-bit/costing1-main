<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('mainToastContainer');
    if (container) {
        const toasts = container.querySelectorAll('.alert');
        toasts.forEach(toast => {
            toast.addEventListener('click', () => {
                toast.classList.add('toast-fadeOut');
                setTimeout(() => toast.remove(), 300);
            });
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    toast.classList.add('toast-fadeOut');
                    setTimeout(() => toast.remove(), 300);
                }
            }, 4000);
        });
    }
});
</script>
