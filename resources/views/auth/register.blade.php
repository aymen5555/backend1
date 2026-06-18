@extends('layouts.app')
@section('title','Register')
@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Create an Account</h2>
    <form id="registerForm">
        <div class="mb-3">
            <label class="form-label" for="first_name">First Name</label>
            <input class="form-control" type="text" id="first_name" name="first_name" required />
        </div>
        <div class="mb-3">
            <label class="form-label" for="last_name">Last Name</label>
            <input class="form-control" type="text" id="last_name" name="last_name" required />
        </div>
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input class="form-control" type="email" id="email" name="email" required />
        </div>
        <div class="mb-3">
            <label class="form-label" for="phone">Phone (optional)</label>
            <input class="form-control" type="text" id="phone" name="phone" />
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input class="form-control" type="password" id="password" name="password" required />
        </div>
        <div class="mb-3">
            <label class="form-label" for="password_confirmation">Confirm Password</label>
            <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" required />
        </div>
        <div class="mb-3">
            <label class="form-label" for="role">Role</label>
            <select class="form-select" id="role" name="role" required>
                <option value="CLIENT" selected>Client</option>
                <option value="GERANT">Gerant de complexe</option>
            </select>
        </div>
        <button class="btn btn-primary" type="submit">Register</button>
    </form>
    <div id="result" class="mt-3"></div>
</div>
<script>
    const form = document.getElementById('registerForm');
    const resultDiv = document.getElementById('result');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(form));
        try {
            const resp = await fetch('/api/auth/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const json = await resp.json();
            if (json.success) {
                resultDiv.innerHTML = `<div class="alert alert-success">Registered! Token: <code>${json.data.token}</code></div>`;
            } else {
                resultDiv.innerHTML = `<div class="alert alert-danger">${JSON.stringify(json.errors || json.message)}</div>`;
            }
        } catch (err) {
            resultDiv.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
        }
    });
</script>
@endsection
