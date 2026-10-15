<table class="top"><tr>
    <td>@if ($logo)<img src="{{ $logo }}" class="logo" alt="">@else<span class="brand">HAVOC</span>@endif</td>
    <td class="doc"><div class="lbl">{{ $t['presskit'] }}</div><div class="date">{{ $t['as_of'] }} {{ now()->format('d.m.Y') }}</div></td>
</tr></table>
<div class="rule"></div>
