@extends('layouts.app')
@section('title','Login')
@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Sign In</h2>
    <form id="loginForm">
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input class="form-control" type="email" id="email" name="email" required />
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input class="form-control" type="password" id="password" name="password" required />
        </div>
        <button class="btn btn-primary" type="submit">Login</button>
    </form>
    <div id="result" class="mt-3"></div>
</div>
<script>
    const form = document.getElementById('loginForm');
    const resultDiv = document.getElementById('result');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(form));
        try {
            const resp = await fetch('/api/auth/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const json = await resp.json();
            if (json.success) {
                resultDiv.innerHTML = `<div class="alert alert-success">Logged in! Token: <code>${json.data.token}</code></div>`;
            } else {
                resultDiv.innerHTML = `<div class="alert alert-danger">${JSON.stringify(json.errors || json.message)}</div>`;
            }
        } catch (err) {
            resultDiv.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
        }
    });
</script>
@endsection
