<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ICT Operations Login</title>
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body class="login-page">
    <div class="login-card">
        <div class="login-head">
            <h1>TEACHING HOSPITAL PERADENIYA</h1>
            <div>ICT Operations, Support & Maintenance</div>
        </div>

        <div class="login-body">
            @if($errors->any())
                <div class="alert alert-error">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ route('login.store') }}">
                @csrf

                <div class="form-group">
                    <label for="login">Username or Email</label>
                    <input
                        id="login"
                        class="form-control"
                        name="login"
                        value="{{ old('login') }}"
                        autocomplete="username"
                        autofocus
                        required
                    >
                </div>

                <div class="form-group" style="margin-top: 13px;">
                    <label for="password">Password</label>
                    <div style="display: flex; gap: 6px;">
                        <input
                            id="password"
                            class="form-control"
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            required
                        >
                        <button
                            class="btn btn-light"
                            type="button"
                            data-password-toggle="#password"
                        >
                            Show
                        </button>
                    </div>
                </div>

                <label style="display: flex; gap: 7px; align-items: center; margin: 13px 0;">
                    <input type="checkbox" name="remember" value="1">
                    Keep me signed in on this authorised device
                </label>

                <button class="btn btn-primary" style="width: 100%;" type="submit">
                    Sign In
                </button>
            </form>

            <div class="login-note">
                Authorised personnel only. Access and significant actions are logged for
                security, governance and administrative audit. Do not enter patient clinical
                information into routine ICT support records unless specifically authorised.
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/app.js') }}" defer></script>
</body>
</html>
