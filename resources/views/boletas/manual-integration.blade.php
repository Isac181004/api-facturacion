@include('boletas.manual')
<script src="{{ asset('js/manual-login-fix.js') }}?v={{ @filemtime(public_path('js/manual-login-fix.js')) ?: time() }}"></script>
