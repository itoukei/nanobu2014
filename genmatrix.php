<?php
  if (is_array($_FILES) && is_array($_FILES['pngfile'])) {
    print_r($_FILES);
    echo '<br/>';
    $source=$_FILES['pngfile']['tmp_name'];
    $image=imagecreatefrompng($source);
    $width=imagesx($image);
    $height=imagesy($image);
    printf("%d x %d\n",$width,$height);
    $step=10;
    if (isset($_POST['blocksize']) && preg_match('/[0-9]+/',$_POST['blocksize'],$r)) {
      $step=$r[0];
    }
    printf("%d x %d block\n",$step,$step);
    $xoffset=0;
    if (isset($_POST['xoffset']) && preg_match('/[0-9]+/',$_POST['xoffset'],$r)) {
      $xoffset=$r[0];
    }
    $yoffset=0;
    if (isset($_POST['yoffset']) && preg_match('/[0-9]+/',$_POST['yoffset'],$r)) {
      $yoffset=$r[0];
    }
    printf("xoffset %d, yoffset %d\n",$xoffset,$yoffset);
    $waku=imagecolorallocate($image,100,100,255);
    for ($y=$yoffset;$y<$height;$y+=$step) {
      for ($x=$xoffset;$x<$width;$x+=$step) {
	$wcount=0;
	$ocount=0;
	for ($j=0;$j<$step;$j++) {
	  for ($i=0;$i<$step;$i++) {
	    $col=imagecolorat($image,$x+$i,$y+$j);
	    if ($col==0xffffff) {
	      $wcount++;
	    } else {
	      $ocount++;
	    }
	  }
	}
	if ($ocount>=$wcount) {
	  imagerectangle($image,$x,$y,$x+$step-1,$y+$step-1,$waku);
	}
      }
    }
    $fn=tempnam("tmp","genmat");
    imagepng($image,$fn);
    printf("<div><img src=\"%s\"/></div>",str_replace(getcwd().'/','',$fn));
    return;
  }
?>
<form method="post" enctype="multipart/form-data">
<table>
<tr><td>pngfile</td><td><input type=file name="pngfile" /></td></tr>
<tr><td>blocksize</td><td><input type=text name="blocksize" value="10"/></td></tr>
<tr><td>xoffset</td><td><input type=text name="xoffset" value="0" /></td></tr>
<tr><td>yoffset</td><td><input type=text name="yoffset" value="0" /></td></tr>
<tr><td></td><td><input type=submit value="Upload" /></td></tr>
</table>
</form>