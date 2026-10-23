<!DOCTYPE html>
<html lang="en">
<body style="font-family: Arial, sans-serif; color: #1a2233; line-height: 1.5;">
    <p>Hi {{ $name }},</p>

    @if ($purpose === \App\Enums\CodePurpose::Register)
        <p>Welcome to HELP2U. Your account is created once you verify your email. Open the link below and enter this code.</p>
    @else
        <p>Use this code to finish logging in to HELP2U.</p>
    @endif

    <p style="font-size: 28px; font-weight: bold; letter-spacing: 6px;">{{ $code }}</p>

    <p>This code expires in {{ \App\Services\VerificationService::CODE_EXPIRY_MINUTES }} minutes.</p>

    @if ($link)
        <p><a href="{{ $link }}">Verify my account</a></p>
        <p>The link expires in {{ \App\Services\VerificationService::LINK_EXPIRY_MINUTES }} minutes.</p>
    @endif

    <p>If you did not request this, you can ignore this email.</p>
</body>
</html>
