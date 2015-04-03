<ul class="pagination">
    <?php if (!empty($pg['firstPage'])) { ?>
    <li><a class="first" href="<?php echo $BASE . $pg['route'] . $pg['prefix'] . $pg['firstPage'] . $pg['routeSuffix']; ?>">First</a></li>
    <?php } ?>
    <?php if (!empty($pg['prevPage'])) { ?>
    <li><a class="previous" href="<?php echo $BASE . $pg['route'] . $pg['prefix'] . $pg['prevPage'] . $pg['routeSuffix']; ?>"><i class="glyphicon glyphicon-chevron-left"></i></a></li>
    <?php } ?>

    <?php if (!empty($pg['rangePages'])) { foreach ($pg['rangePages'] as $page) { ?>
    <li <?php if ($page == $pg['currentPage']) { echo "class='active'"; } ?>><a href="<?php echo $BASE . $pg['route'] . $pg['prefix'] . $page . $pg['routeSuffix']; ?>"><?php echo $page; ?></a></li>
    <?php } } ?>
	
    <?php if (!empty($pg['nextPage'])) { ?>
    <li><a class="next" href="<?php echo $BASE . $pg['route'] . $pg['prefix'] . $pg['nextPage'] . $pg['routeSuffix']; ?>"><i class="glyphicon glyphicon-chevron-right"></i></a></li>
    <?php } ?>
    <?php if (!empty($pg['lastPage'])) { ?>
    <li><a class="last" href="<?php echo $BASE . $pg['route'] . $pg['prefix'] . $pg['lastPage'] . $pg['routeSuffix']; ?>">Last [<?php echo $pg['lastPage'] ?>]</a></li>
    <?php } ?>
</ul>