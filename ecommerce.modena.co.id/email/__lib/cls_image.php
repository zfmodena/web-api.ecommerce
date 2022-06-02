<?
class image_processing{
	private $data=array();
	private $arr_watermark_position=array("h_position"/*left|center|right*/, "v_position"/*top|center|bottom*/, "watermark_size"/*larger|x%*/);
	private $arr_watermark_position_value=array("h_position"=>"left", "v_position"=>"top", "watermark_size"=>"larger");
	
	public function __set($name, $value){
		$this->data[$name] = $value;
	}
	
	public function __get($name){
		return $this->data[$name];
	}

	public function __isset($name) {
        return isset($this->data[$name]);
    }
	
	public function __unset($name) {
        unset($this->data[$name]);
    }

/* ############################# metode umum ########################################*/
	private function set_default_watermark_option(){		
		foreach($arr_watermark_position as $value){
			if(!isset($this->data[$value]))$this->data[$value]=$arr_watermark_position_value[$value]	;
		}
	}

	public function watermarking($filename, $path, $watermark, $watermark_mode/*text|image*/){		
		$this->set_default_watermark_option();
		$original=dirname(__FILE__).$path.$filename;
		$target=dirname(__FILE__).$path.$filename;
		$wmTarget=$watermark;
		if($watermark_mode=="image"){
			$watermark=dirname(__FILE__).$watermark;
			$waterMarkInfo = getimagesize($watermark);
			$waterMarkWidth = $waterMarkInfo[0];
			$waterMarkHeight = $waterMarkInfo[1];			
			$wmTarget.=".tmp";
			$size=getimagesize($original);
		}		

		$origInfo = getimagesize($target); 
		print_r($origInfo);
		$origWidth = $origInfo[0]; 
		$origHeight = $origInfo[1]; 
		
		if(isset($this->data["watermark_size"])=="larger"){
			$placementX=0;
			$placementY=0;
			$this->data["h_position"]="center";
			$this->data["v_position"]='center';
			$waterMarkDestWidth=$waterMarkWidth;
			$waterMarkDestHeight=$waterMarkHeight;
			
			// both of the watermark dimensions need to be 5% more than the original image...
			// adjust width first.
			if($waterMarkWidth > $origWidth*1.05 && $waterMarkHeight > $origHeight*1.05){
				// both are already larger than the original by at least 5%...
				// we need to make the watermark *smaller* for this one.
				
				// where is the largest difference?
				$wdiff=$waterMarkDestWidth - $origWidth;
				$hdiff=$waterMarkDestHeight - $origHeight;
				if($wdiff > $hdiff){
					// the width has the largest difference - get percentage
					$sizer=($wdiff/$waterMarkDestWidth)-0.05;
				}else{
					$sizer=($hdiff/$waterMarkDestHeight)-0.05;
				}
				$waterMarkDestWidth-=$waterMarkDestWidth * $sizer;
				$waterMarkDestHeight-=$waterMarkDestHeight * $sizer;
			}else{
			// the watermark will need to be enlarged for this one
			
			// where is the largest difference?
				$wdiff=$origWidth - $waterMarkDestWidth;
				$hdiff=$origHeight - $waterMarkDestHeight;
				if($wdiff > $hdiff){
					// the width has the largest difference - get percentage
					$sizer=($wdiff/$waterMarkDestWidth)+0.05;
				}else{
					$sizer=($hdiff/$waterMarkDestHeight)+0.05;
				}
				$waterMarkDestWidth+=$waterMarkDestWidth * $sizer;
				$waterMarkDestHeight+=$waterMarkDestHeight * $sizer;
			}
		}else{
			$waterMarkDestWidth=round($origWidth * floatval($this->data["watermark_size"]));
			$waterMarkDestHeight=round($origHeight * floatval($this->data["watermark_size"]));
			if($this->data["watermark_size"]==1){
				$waterMarkDestWidth-=2*$edgePadding;
				$waterMarkDestHeight-=2*$edgePadding;
			}
		}
		if($watermark_mode=="image"){
			$this->resize_png_image($watermark,$waterMarkDestWidth,$waterMarkDestHeight,$wmTarget);
			// get the size info for this watermark.
			$wmInfo=getimagesize($wmTarget);
			$waterMarkDestWidth=$wmInfo[0];
			$waterMarkDestHeight=$wmInfo[1];
	
			$differenceX = $origWidth - $waterMarkDestWidth;
			$differenceY = $origHeight - $waterMarkDestHeight;
		}
				
		// where to place the watermark?
		switch($_POST['h_position']){
			// find the X coord for placement
			case 'left':
			$placementX = $edgePadding;
			break;
			case 'center':
			$placementX =  round($differenceX / 2);
			break;
			case 'right':
			$placementX = $origWidth - $waterMarkDestWidth - $edgePadding;
			break;
		}
		
		switch($_POST['v_position']){
			// find the Y coord for placement
			case 'top':
			$placementY = $edgePadding;
			break;
			case 'center':
			$placementY =  round($differenceY / 2);
			break;
			case 'bottom':
			$placementY = $origHeight - $waterMarkDestHeight - $edgePadding;
			break;
		}
		
		if($size[2]==3)
			$resultImage = imagecreatefrompng($original);
		else
			$resultImage = imagecreatefromjpeg($original);
		imagealphablending($resultImage, TRUE);
		
		$finalWaterMarkImage = imagecreatefrompng($wmTarget);
		$finalWaterMarkWidth = imagesx($finalWaterMarkImage);
		$finalWaterMarkHeight = imagesy($finalWaterMarkImage);
		
		imagecopy($resultImage,
			$finalWaterMarkImage,
			$placementX,
			$placementY,
			0,
			0,
			$finalWaterMarkWidth,
			$finalWaterMarkHeight
		);
		
		if($size[2]==3){
			imagealphablending($resultImage,FALSE);
			imagesavealpha($resultImage,TRUE);
			imagepng($resultImage,$target,$quality);
		}else{
			imagejpeg($resultImage,$target,$quality); 
		}
		
		imagedestroy($resultImage);
		imagedestroy($finalWaterMarkImage);
		return $target;	
	}

	private function resize_png_image($img,$newWidth,$newHeight,$target){
		$srcImage=imagecreatefrompng($img);
		if($srcImage==''){return FALSE;}
		$srcWidth=imagesx($srcImage);
		$srcHeight=imagesy($srcImage);
		$percentage=(double)$newWidth/$srcWidth;
		$destHeight=round($srcHeight*$percentage)+1;
		$destWidth=round($srcWidth*$percentage)+1;
		if($destHeight > $newHeight){
			// if the width produces a height bigger than we want, calculate based on height
			$percentage=(double)$newHeight/$srcHeight;
			$destHeight=round($srcHeight*$percentage)+1;
			$destWidth=round($srcWidth*$percentage)+1;
		}
		$destImage=imagecreatetruecolor($destWidth-1,$destHeight-1);
		if(!imagealphablending($destImage,FALSE)){return FALSE;}
		if(!imagesavealpha($destImage,TRUE)){return FALSE;}
		if(!imagecopyresampled($destImage,$srcImage,0,0,0,0,$destWidth,$destHeight,$srcWidth,$srcHeight)){return FALSE;}
		if(!imagepng($destImage,$target)){return FALSE;}
		imagedestroy($destImage);
		imagedestroy($srcImage);
		return TRUE;
	}

	public function text_imagewatermarking(){
		$im = imagecreatefromjpeg("example.jpg"); 
		
		
		//figure out where to put the text
		$imagesize = getimagesize("example.jpg");
		$x_offset = 7;
		$y_offset = $imagesize[1] - 20;
		
		//allocate text color
		$textcolor = imagecolorallocate($im,0xFF, 0x00, 0x34 );
		
		//write out the watermark
		imagestring($im, 5, $x_offset, $y_offset, 'watermark text goes here', $textcolor);
		
		//output watermarked image
		header('Content-type: image/jpg');
		imagejpeg($im);	
	}
	
	public function deletefile($filename){
	
	}
}
?>