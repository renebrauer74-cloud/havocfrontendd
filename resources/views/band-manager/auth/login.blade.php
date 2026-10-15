<x-band-manager.layout title="Anmelden">
    <div class="mx-auto" style="max-width:400px">
        <div class="card">
            <div class="card-body p-4">
                <h1 class="h5 mb-1">Anmelden</h1>
                <p class="text-muted small mb-4">Melde dich mit deinen Zugangsdaten an.</p>
                <form method="post" action="{{ route('band-manager.login.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="email">E-Mail</label>
                        <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autofocus autocomplete="username">
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="password">Passwort</label>
                        <input type="password" name="password" id="password" class="form-control" required autocomplete="current-password">
                    </div>
                    <button class="btn btn-primary w-100 py-2">Anmelden</button>
                </form>
            </div>
        </div>
    </div>
</x-band-manager.layout>
