    <script>
        @include('form.partials.scripts.imports-and-state')
        @include('form.partials.scripts.material-calculation')
        @include('form.partials.scripts.unpriced-parts')
        @include('form.partials.scripts.costing-resume')
        @include('form.partials.scripts.material-editor')
        @include('form.partials.scripts.material-table-controls')
        @include('form.partials.scripts.rates-and-cycle-time')
        @include('form.partials.scripts.initialization')
    </script>
    @if(!empty($readOnlyMode))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.readonly-costing input, .readonly-costing select, .readonly-costing textarea, .readonly-costing button').forEach(function (control) {
                control.disabled = true;
                control.setAttribute('aria-disabled', 'true');
            });
        });
    </script>
    @endif
