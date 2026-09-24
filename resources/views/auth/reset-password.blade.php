<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Reset Password | BUMDESMA LKD TARUB</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<style>
		:root { --blue: #106fae; --blue-dark: #075487; --blue-light: #159bd0; --ink: #173247; }
		body { min-height: 100vh; background: #eef5fa; color: var(--ink); }
		.auth-card { border: 0; border-radius: 1.25rem; box-shadow: 0 30px 70px rgba(7, 84, 135, .18); }
		.auth-icon { display: inline-grid; place-items: center; width: 64px; height: 64px; border-radius: 1rem; background: linear-gradient(135deg, var(--blue-dark), var(--blue-light)); color: #fff; font-size: 1.6rem; }
		.btn-primary { background: linear-gradient(135deg, var(--blue-dark), var(--blue-light)); border: 0; }
	</style>
</head>
<body>
	<main class="container d-flex align-items-center justify-content-center min-vh-100 py-5">
		<div class="auth-card bg-white p-4 p-md-5 w-100" style="max-width: 480px;">
			<div class="text-center mb-4">
				<span class="auth-icon mb-3">
				    <i class="bi bi-key">
				    </i>
				</span>
				<h1 class="h4 fw-bold mb-1">Buat password baru</h1>
				<p class="text-secondary small mb-0">Password minimal 8 karakter.</p>
			</div>
			<form method="POST" action="{{ route('password.update') }}">
				@csrf
				<input type="hidden" name="token" value="{{ $token }}">
				<div class="mb-3">
					<label class="form-label fw-semibold small" for="email">Email</label>
					<input
					    class="form-control form-control-lg @error('email') is-invalid @enderror"
					    id="email"
					    name="email"
					    type="email"
					    value="{{ old('email', request('email')) }}"
					    required
					    autofocus>
					@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
				</div>
				<div class="mb-3">
					<label class="form-label fw-semibold small" for="password">Password Baru</label>
					<input
					    class="form-control form-control-lg @error('password') is-invalid @enderror"
					    id="password"
					    name="password"
					    type="password"
					    minlength="8"
					    autocomplete="new-password"
					    required>
					@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
				</div>
				<div class="mb-4">
					<label class="form-label fw-semibold small" for="password_confirmation">Konfirmasi Password Baru</label>
					<input
					    class="form-control form-control-lg"
					    id="password_confirmation"
					    name="password_confirmation"
					    type="password"
					    minlength="8"
					    autocomplete="new-password"
					    required>
				</div>
				<button class="btn btn-primary btn-lg w-100" type="submit">
				    <i class="bi bi-check-circle me-2">
				    </i>Simpan Password Baru</button>
			</form>
		</div>
	</main>
</body>
</html>
