@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display:inline-block;text-decoration:none;">
<img src="cid:libralms-logo" alt="{{ config('app.name', 'LibraLMS') }}" class="logo" width="64" height="64">
<div class="brand-name">{{ config('app.name', 'LibraLMS') }}</div>
</a>
</td>
</tr>
