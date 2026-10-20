<x-band-manager.layout title="Passwort vergessen">
    <div class="mx-auto" style="max-width:400px">
        <div class="card">
            <div class="card-body p-4">
                <h1 class="h5 mb-1">Passwort vergessen?</h1>
                <p class="text-muted small mb-4">Gib die E-Mail-Adresse deines Logins ein. Wir schicken dir einen Link, mit dem du ein neues Passwort festlegst.</p>
                <form method="post" action="{{ route('band-manager.password.email') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label" for="email">E-Mail</label>
                        <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autofocus autocomplete="username">
                    </div>
                    <button class="btn btn-primary w-100 py-2">Link senden</button>
                </form>
                <div class="text-center mt-3">
                    <a href="{{ route('band-manager.login') }}" class="small"><i class="bi bi-arrow-left"></i> Zurück zur Anmeldung</a>
                </div>
            </div>
        </div>
    </div>
</x-band-manager.layout>
