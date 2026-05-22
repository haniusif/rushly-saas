<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            margin-top: 80px;
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center">
        <div class="card w-50">
            <div class="card-header bg-danger text-white">
                <h4 class="mb-0">Delete Account</h4>
            </div>
            <div class="card-body">
                <p class="text-danger">⚠️ Are you sure you want to delete this account? This action cannot be undone.</p>

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @if(request()->user_id)
                    <div class="alert alert-success">Account deleted successfully.</div>
                @endif

                <form action="{{ route('account_delete') }}" method="get">
                    @csrf

                    <div class="form-group">
                        <label for="user_id">Enter Driver ID or Merchant ID</label>
                        <input type="text" name="user_id" id="user_id" class="form-control" required placeholder="e.g., 1024">
                    </div>

                    <button type="submit" class="btn btn-danger">Yes, Delete Account</button>
                    <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
