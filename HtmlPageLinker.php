<?php
/**
 * Linking web page as one file after saving as "Web Page, complete" in a browser.
 * 
 * @author earthperson <ponomarev.dev@gmail.com>
 * @version 1.0.1
 */
class HtmlPageLinker {

	/** @var string Path to the folder with files for saved HTML page */
	private $_resources;
	
	/** @var string Base path */
	private $_path;
	
	/** @var string Saved HTML page name */
	private $_page;
	
	/** @var boolean Delete or not included into output page resources */
	private $_unlink;
	
	/** @var string Override output page or save with suffix */
	private $_out_suffix;

	public function __construct($resources, $path = __DIR__) {
		$this->_resources = $this->_directory($resources);
		$this->_path = $this->_directory($path);
	}
	
	public function process($page, $unlink = false, $out_suffix = '') {
		$this->_page = $page;
		if(is_dir($this->_path . $this->_resources) && is_file($this->_path . $this->_page)) {
			$this->_unlink = $unlink;
			$this->_out_suffix = $out_suffix;
			$this->_dataUriPage();
			$this->_dataUriResources();
			$this->_linkCss();
			$this->_linkJavaScript();
		}
	}

	private function _dataUriPage() {
		$text = file_get_contents($this->_path . $this->_page);
		$m = array();
		preg_match_all("/\\<img.*?src=[\"'](.*?)[\"'].*?\\>/s", $text, $m);
		if (isset($m[1]) && is_array($m[1])) {
			foreach ($m[1] as $img) {
				if ($data_uri = $this->_dataUri($img)) {
					$text = str_replace($img, $data_uri, $text);
				}
			}
			file_put_contents($this->_path . $this->_page . $this->_out_suffix, $text);
		}
	}

	private function _dataUriResources() {
		foreach (scandir($this->_path . $this->_resources) as $file) {
			if(substr($file, -4) == '.css') {
				$f = $this->_path . $this->_resources . $file;
				$text = file_get_contents($f);
				$m = array();
				preg_match_all("/\\s+background\\:\\s+url\\((.*?)\\)/s", $text, $m);
				if (isset($m[1]) && is_array($m[1])) {
					foreach ($m[1] as $background) {
						if ($data_uri = $this->_dataUri($background)) {
							$text = str_replace($background, '\'' . $data_uri . '\'', $text);
						}
					}
					file_put_contents($f . $this->_out_suffix, $text);
				}
			}
		}
	}

	private function _dataUri($file) {
		$f = $this->_path . $this->_resources . basename($file);
		if (is_file($f)) {
			$mime = 'image/' . pathinfo($file, PATHINFO_EXTENSION);
			$contents = file_get_contents($f);
			if($this->_unlink) {
				@unlink($f);
			}
			$base64 = base64_encode($contents);
			return "data:$mime;base64,$base64";
		}
	}
	
	private function _linkCss() {
		$text = file_get_contents($this->_path . $this->_page . $this->_out_suffix);
		$m = array();
		preg_match_all("/\\<link.*?href=[\"'](.*?)[\"'].*?\\>/s", $text, $m, PREG_SET_ORDER);
		foreach ($m as $item) {
			if (isset($item[0], $item[1])) {
				$file = $item[1];
				if(substr($file, -4) == '.css') {
					$f = $this->_path . $this->_resources . basename($file) . $this->_out_suffix;
					if (is_file($f)) {
						$contents = file_get_contents($f);
						if($this->_unlink) {
							@unlink($f);
						}
						$text = str_replace(
							$item[0],
							'<style type="text/css">' . $contents . '</style>',
							$text
						);
					}
				}
			}
		}
		file_put_contents($this->_path . $this->_page . $this->_out_suffix, $text);
	}
	
	private function _linkJavaScript() {
		$text = file_get_contents($this->_path . $this->_page . $this->_out_suffix);
		$m = array();
		preg_match_all("/\\<script.*?src=[\"'](.*?)[\"'].*?\\>\\<\\/script\\>/s", $text, $m, PREG_SET_ORDER);
		foreach ($m as $item) {
			if (isset($item[0], $item[1])) {
				$file = $item[1];
				if(substr($file, -3) == '.js') {
					$f = $this->_path . $this->_resources . basename($file) . $this->_out_suffix;
					if (is_file($f)) {
						$contents = file_get_contents($f);
						if($this->_unlink) {
							@unlink($f);
						}
						$text = str_replace(
								$item[0],
								'<script type="text/javascript">' . $contents . '</script>',
								$text
						);
					}
				}
			}
		}
		file_put_contents($this->_path . $this->_page . $this->_out_suffix, $text);
	}
	
	private function _directory($path) {
		return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
	}
}
