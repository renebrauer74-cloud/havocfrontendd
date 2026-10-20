<x-band-manager.layout title="Mein Konto">
    <h1 class="h3 mb-1">Mein Konto</h1>
    <p class="text-muted mb-4">Deine Zugangsdaten für den {{ config('band-manager.name') }}.</p>

    <div class="d-flex flex-column gap-4" style="max-width:760px">

        {{-- Stammdaten --}}
        <form method="post" action="{{ route('band-manager.account.update') }}" class="card card-body shadow-sm">
            @csrf
            @method('PUT')
            <h2 class="h5 mb-3"><i class="bi bi-person me-1"></i> {{ $bands->isNotEmpty() ? 'Name & Band' : 'Name' }}</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="name">Dein Name *</label>
                    <input name="name" id="name" class="form-control" required maxlength="150" value="{{ old('name', $user->name) }}" autocomplete="name">
                    <div class="form-text">So erscheinst du im Band-Manager, z. B. unten links im Menü.</div>
                </div>
                @foreach ($bands as $band)
                    <div class="col-md-6">
                        <label class="form-label" for="band-name-{{ $band->id }}">Bandname *</label>
                        <input name="band_names[{{ $band->id }}]" id="band-name-{{ $band->id }}" class="form-control" required maxlength="150"
                               value="{{ old('band_names.'.$band->id, $band->name) }}">
                    </div>
                @endforeach
            </div>
            <div class="mt-4">
                <button class="btn btn-primary">Speichern</button>
            </div>
        </form>

        @if ($isImpersonating)
            <div class="alert alert-info mb-0">
                <i class="bi bi-info-circle me-1"></i> E-Mail-Adresse und Passwort kann nur {{ $user->name }} selbst ändern. Als Admin geht das unter Administration → Benutzer.
            </div>
        @else
            {{-- E-Mail-Adresse --}}
            <form method="post" action="{{ route('band-manager.account.email.update') }}" class="card card-body shadow-sm">
                @csrf
                @method('PUT')
                <h2 class="h5 mb-1"><i class="bi bi-envelope me-1"></i> E-Mail-Adresse</h2>
                <p class="text-muted small mb-3">
                    Mit dieser Adresse meldest du dich an: <strong>{{ $user->email }}</strong><br>
                    Wir schicken dir einen Link an die neue Adresse. Erst wenn du darauf klickst, wird sie dein Login.
                </p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="email">Neue E-Mail-Adresse *</label>
                        <input type="email" name="email" id="email" class="form-control" required maxlength="190" value="{{ old('email') }}" autocomplete="email">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email_password">Aktuelles Passwort *</label>
                        <input type="password" name="email_password" id="email_password" class="form-control" required autocomplete="current-password">
                    </div>
                </div>
                <div class="mt-4">
                    <button class="btn btn-primary">Bestätigungslink senden</button>
                </div>
            </form>

            {{-- Passwort --}}
            <form method="post" action="{{ route('band-manager.account.password.update') }}" class="card card-body shadow-sm">
                @csrf
                @method('PUT')
                <h2 class="h5 mb-1"><i class="bi bi-key me-1"></i> Passwort</h2>
                <p class="text-muted small mb-3">Mindestens {{ $passwordMin }} Zeichen. Nach der Änderung bekommst du zur Sicherheit eine E-Mail.</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="current_password">Aktuelles Passwort *</label>
                        <input type="password" name="current_password" id="current_password" class="form-control" required autocomplete="current-password">
                    </div>
                    <div class="w-100 d-none d-md-block"></div>
                    <div class="col-md-6">
                        <label class="form-label" for="password">Neues Passwort *</label>
                        <input type="password" name="password" id="password" class="form-control" required minlength="{{ $passwordMin }}" autocomplete="new-password">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password_confirmation">Neues Passwort wiederholen *</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required minlength="{{ $passwordMin }}" autocomplete="new-password">
                    </div>
                </div>
                <div class="mt-4">
                    <button class="btn btn-primary">Passwort ändern</button>
                </div>
            </form>
        @endif

    </div>
</x-band-manager.layout>
