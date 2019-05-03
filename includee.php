<?php if(include('lazyInclude.php')) return // この先の評価が不要な場合、lazyInclude.phpはtrueを返すのでreturnで中断する ?>
<div>Sub File</div>
<?php endLazyInclude() ?>