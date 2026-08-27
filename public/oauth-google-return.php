<?php

declare(strict_types=1);

/**
 * Google OAuth bridge for HostGator ModSecurity.
 *
 * ModSecurity returns HTTP 406 for GET callbacks whose query string contains
 * "userinfo.profile" (common when Google re-sends previously granted scopes).
 * Using response_mode=form_post, Google POSTs here instead; we forward only
 * code/state/error to Laravel via a clean same-site GET (session cookie intact).
 */

$code = '';
$state = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $code = trim((string) ($_POST['code'] ?? ''));
    $state = trim((string) ($_POST['state'] ?? ''));
    $error = trim((string) ($_POST['error'] ?? ''));
} else {
    $code = trim((string) ($_GET['code'] ?? ''));
    $state = trim((string) ($_GET['state'] ?? ''));
    $error = trim((string) ($_GET['error'] ?? ''));
}

$query = [];
if ($error !== '') {
    $query['error'] = $error;
}
if ($code !== '') {
    $query['code'] = $code;
}
if ($state !== '') {
    $query['state'] = $state;
}

$target = 'https://www.tesora.com.br/auth/google/callback';
if ($query !== []) {
    $target .= '?'.http_build_query($query);
}

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Location: '.$target, true, 302);
exit;
