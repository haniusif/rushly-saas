<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rate your delivery</title>
    <link rel="shortcut icon" href="{{ settings()->favicon_image }}" />
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #f6f7f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Cairo', sans-serif; color: #1f2937; }
        .wrap { max-width: 480px; margin: 24px auto; padding: 0 16px; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 1px 3px rgb(0 0 0 / 0.08); padding: 24px; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        .sub { color: #6b7280; font-size: 13px; margin-bottom: 18px; }
        .row { display: flex; gap: 8px; align-items: center; justify-content: center; padding: 12px 0; }
        .stars { display: flex; gap: 6px; justify-content: center; }
        .star { width: 44px; height: 44px; border-radius: 50%; border: 1px solid #e5e7eb; background: #fff; font-size: 22px; cursor: pointer; }
        .star input { display: none; }
        .stars input:checked ~ label, .stars label.active { background: #fef3c7; border-color: #f59e0b; }
        textarea { width: 100%; min-height: 100px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 12px; font: inherit; font-size: 14px; resize: vertical; }
        .btn { width: 100%; padding: 14px; background: #a21f5c; color: #fff; border: 0; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 16px; }
        .btn:hover { background: #831d4d; }
        .pinfo { background: #f9fafb; border-radius: 10px; padding: 12px; margin-bottom: 16px; font-size: 13px; color: #4b5563; }
        .err { color: #dc2626; font-size: 13px; margin-top: 6px; }
        .prev-note { background: #ecfdf5; color: #047857; padding: 10px 12px; border-radius: 10px; margin-bottom: 16px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>How was your delivery?</h1>
            <p class="sub">Tell us how we did. Takes 5 seconds.</p>

            <div class="pinfo">
                <strong>Tracking:</strong> {{ $parcel->tracking_id }}<br/>
                <strong>Delivered to:</strong> {{ $parcel->customer_name }}
            </div>

            @if ($existing)
                <div class="prev-note">You already rated this delivery {{ $existing->rating }}/5. You can update it below.</div>
            @endif

            <form method="POST" action="{{ $submit }}">
                @csrf
                <div class="stars" role="radiogroup" aria-label="Rating">
                    @for ($i = 1; $i <= 5; $i++)
                        <label class="star {{ $existing && $existing->rating === $i ? 'active' : '' }}">
                            <input type="radio" name="rating" value="{{ $i }}" {{ $existing && $existing->rating === $i ? 'checked' : '' }} required>
                            <span aria-hidden="true">★</span>
                            <span style="position:absolute;left:-9999px">{{ $i }} stars</span>
                        </label>
                    @endfor
                </div>
                @error('rating') <div class="err">{{ $message }}</div> @enderror

                <div class="row" style="flex-direction:column;align-items:stretch">
                    <textarea name="comment" placeholder="Anything you'd like the team to know? (optional)">{{ $existing->comment ?? '' }}</textarea>
                    @error('comment') <div class="err">{{ $message }}</div> @enderror
                </div>

                <button class="btn" type="submit">Submit rating</button>
            </form>
        </div>
    </div>

    {{-- Single tiny click handler: keeps zero JS framework dependency, just paint
         the selected star. The form still works fine without JS. --}}
    <script>
        document.querySelectorAll('.stars label').forEach((el) => {
            el.addEventListener('click', () => {
                document.querySelectorAll('.stars label').forEach((x) => x.classList.remove('active'));
                el.classList.add('active');
            });
        });
    </script>
</body>
</html>
