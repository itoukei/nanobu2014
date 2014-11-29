<?php
  if (isset($_GET['source']) && file_exists($_GET['source'])) {
    $source=$_GET['source'];
    $step=$_GET['blocksize'];
    $xoffset=$_GET['xoffset'];
    $yoffset=$_GET['yoffset'];
    $blockorder=explode(',',$_GET['order']);

    $image=imagecreatefrompng($source);
    $width=imagesx($image);
    $height=imagesy($image);

    // 1x1の枠画像
    $waku=imagecolorallocate($image,100,100,255);

    // ブロックを置くべき場所を取得しながら、ひとまず1x1の枠を描画
    $matrix=array();
    for ($y=$yoffset;$y<$height;$y+=$step) {
      for ($x=$xoffset;$x<$width;$x+=$step) {
	$wcount=0;
	$ocount=0;
	for ($j=0;$j<$step;$j++) {
	  for ($i=0;$i<$step;$i++) {
	    $col=imagecolorat($image,$x+$i,$y+$j);
	    if ($col==0xffffff) {
	      $wcount++; // 白いピクセルを数える
	    } else {
	      $ocount++; // 白以外のピクセルを数える
	    }
	  }
	}
	if ($ocount>=$wcount) { // 白の方が少なければ枠画像表示
	  imagerectangle($image,$x,$y,$x+$step-1,$y+$step-1,$waku);
	  $mx=($x-$xoffset)/$step;
	  $my=($y-$yoffset)/$step;
	  if (!isset($matrix[$mx])) $matrix[$mx]=array();
	  $matrix[$mx][$my]=1;
	}
      }
    }

    // 各サイズのブロックの境界色と塗りつぶし色の設定
    $border['2x4']=imagecolorallocate($image,255,80,80);
    $color['2x4']=imagecolorallocate($image,255,120,120);
    $border['4x2']=imagecolorallocate($image,40,200,40);
    $color['4x2']=imagecolorallocate($image,120,255,120);
    $border['2x2']=imagecolorallocate($image,255,80,255);
    $color['2x2']=imagecolorallocate($image,255,120,255);
    $border['1x4']=imagecolorallocate($image,200,80,80);
    $color['1x4']=imagecolorallocate($image,200,120,120);
    $border['4x1']=imagecolorallocate($image,40,200,40);
    $color['4x1']=imagecolorallocate($image,120,200,120);
    $border['1x3']=imagecolorallocate($image,200,80,80);
    $color['1x3']=imagecolorallocate($image,200,120,120);
    $border['3x1']=imagecolorallocate($image,40,200,40);
    $color['3x1']=imagecolorallocate($image,120,200,120);
    $border['1x2']=imagecolorallocate($image,160,80,80);
    $color['1x2']=imagecolorallocate($image,160,120,120);
    $border['2x1']=imagecolorallocate($image,40,160,40);
    $color['2x1']=imagecolorallocate($image,120,160,120);

    // m*nのブロックを置けるところに置いてみる...
    $url=sprintf("?source=%s&blocksize=%d&xoffset=%d&yoffset=%d",str_replace(getcwd().'/','',$source),$step,$xoffset,$yoffset);

    foreach ($blockorder as $block) {
	$matrix=putblock($block,$matrix);
    }

    foreach ($matrix as $x => $line) {
      foreach ($line as $y => $dot) {
	if (preg_match('/([0-9]+)x([0-9]+)/',$dot,$r)) {
	  //printf("[%d,%d]=%s\n",$x,$y,$dot);
	  $x1=$x*$step+$xoffset;
	  $y1=$y*$step+$yoffset;
	  $x2=($x+$r[1])*$step+$xoffset-1;
	  $y2=($y+$r[2])*$step+$yoffset-1;
	  imagerectangle($image,$x1,$y1,$x2,$y2,$border[$dot]);
	  imagefilledrectangle($image,$x1+1,$y1+1,$x2-1,$y2-1,$color[$dot]);
	}
      }
    }


    // 枠を重ねた画像を表示
    //$fn=sprintf("%s/blocksize%dxoffset%dyoffset%d.png",$path,$step,$xoffset,$yoffset);
    //imagepng($image,$fn);
    header('Content-type: image/png');
    imagepng($image);
  }

  // 指定サイズのブロックを置ける場所に置く
  function putblock($size,$matrix) {
    if (preg_match('/([0-9]+)x([0-9]+)/',$size,$r)) {
      // 置きたいブロックのサイズ
      $dx=$r[1];
      $dy=$r[2];

      // X方向,Y方向のサイズ取得
      $xmax=0;
      $ymax=0;
      foreach ($matrix as $x => $line) {
	if ($x>$xmax) $xmax=$x;
	foreach ($line as $y => $dot) {
	  if ($y>$ymax) $ymax=$y;
	}
      }

      // ブロックを置ける場所を探す
      $bcount=0;
      for ($y=0;$y<=$ymax;$y++) {
	for ($x=0;$x<=$xmax;$x++) {
	  if (noblock($matrix,$x,$y)) {
	    // ブロックが置けるかどうか判定
	    $count=0;
	    for ($j=0;$j<$dy;$j++) {
	      for ($i=0;$i<$dx;$i++) {
		if (noblock($matrix,$x+$i,$y+$j)) $count++;
	      }
	    }
	    // ブロックを置けるなら置く
	    if ($count==$dx*$dy) {
	      //printf("[%d x %d]",$x,$y);
	      for ($j=0;$j<$dy;$j++) {
		for ($i=0;$i<$dx;$i++) {
		  $matrix[$x+$i][$y+$j]=0;
		}
	      }
	      $matrix[$x][$y]=$size;
	      $bcount++;
	    }
	  }
	}
      }

      //printf("%dx%d block (%d)\n",$dx,$dy,$bcount);
    }


    return $matrix;
  }

  // ブロックが置ける場所かどうか返す
  function noblock($matrix,$x,$y) {
    return (isset($matrix[$x][$y]) && $matrix[$x][$y]==1);
  }

?>
