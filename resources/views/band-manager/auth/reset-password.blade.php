<x-band-manager.layout title="Neues Passwort">
    <div class="mx-auto" style="max-width:400px">
        <div class="card">
            <div class="card-body p-4">
                <h1 class="h5 mb-1">Neues Passwort festlegen</h1>
                <p class="text-muted small mb-4">Mindestens {{ $passwordMin }} Zeichen.</p>
                <form method="post" action="{{ route('band-manager.password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div class="mb-3">
                        <label class="form-label" for="email">E-Mail</label>
                        <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $email) }}" required autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Neues Passwort</label>
                        <input type="password" name="password" id="password" class="form-control" required minlength="{{ $passwordMin }}" autofocus autocomplete="new-password">
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="password_confirmation">Neues Passwort wiederholen</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required minlength="{{ $passwordMin }}" autocomplete="new-password">
                    </div>
                    <button class="btn btn-primary w-100 py-2">Passwort speichern</button>
                </form>
            </div>
        </div>
    </div>
</x-band-manager.layout>
