@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ rtrim(config('app.url'), '/') }}/images/logo.png" class="logo" alt="{{ config('app.name') }}" width="140">
</a>
</td>
</tr>
