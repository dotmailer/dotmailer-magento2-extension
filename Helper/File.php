<?php
namespace Dotdigitalgroup\Email\Helper;

class File
{

	const FILE_FULL_ACCESS_PERMISSION = '777';

	/**
	 * Location of files we are building
	 */

	private $_output_folder; // set in _construct
	private $_output_archive_folder; // set in _construct

	private $delimiter; // set in _construct
	private $enclosure; // set in _construct
	protected $_scopeConfig;
	protected $helper;

	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data  $helper,
		\Magento\Framework\App\Filesystem\DirectoryList $directoryList,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\Filesystem $filesystem
	)
	{
		$this->helper = $helper;
		$this->_scopeConfig = $scopeConfig;
		$this->directoryList = $directoryList;
		$this->filesystem = $filesystem;
		$var = $directoryList->getPath('var');
		$this->_output_folder = $var . DIRECTORY_SEPARATOR . 'export' . DIRECTORY_SEPARATOR . 'email';
		$this->_output_archive_folder = $var .  $this->_output_folder . DIRECTORY_SEPARATOR . 'archive';

		$this->delimiter = ','; // tab character
		$this->enclosure = '"';
	} // end


	public function getOutputFolder()
	{
		$this->pathExists($this->_output_folder);
		return $this->_output_folder;
	} // end

	public function getArchiveFolder()
	{
		$this->pathExists($this->_output_archive_folder);
		return $this->_output_archive_folder;
	} // end

	/* Return the full filepath */
	public  function getFilePath($filename)
	{
		return $this->getOutputFolder() . DIRECTORY_SEPARATOR . $filename;
	}

	public  function archiveCSV($filename)
	{
		$this->moveFile($this->getOutputFolder(), $this->getArchiveFolder(), $filename);
	}

	/**
	 * Moves the output file from one folder to the next
	 * @param $source_folder
	 * @param $dest_folder
	 * @param $filename
	 */
	public function moveFile($source_folder, $dest_folder, $filename )
	{
		// generate the full file paths
		$source_filepath = $source_folder . DIRECTORY_SEPARATOR . $filename;
		$dest_filepath = $dest_folder . DIRECTORY_SEPARATOR . $filename;

		// rename the file
		rename($source_filepath, $dest_filepath);

	} // end


	/**
	 * Output an array to the output file FORCING Quotes around all fields
	 * @param $filepath
	 * @param $csv
	 */
	public function outputForceQuotesCSV($filepath, $csv)
	{
		$fqCsv = $this->arrayToCsv($csv,chr(9),'"',true,false);
		// Open for writing only; place the file pointer at the end of the file. If the file does not exist, attempt to create it.
		$fp = fopen($filepath, "a");

		// for some reason passing the preset delimiter/enclosure variables results in error
		// $this->delimiter $this->enclosure
		if (fwrite($fp, $fqCsv) == 0 ) {
			throw new \Exception('Problem writing CSV file');
		}
		fclose($fp);

	} // end


	/**
	 * Output an array to the output file
	 * @param $filepath
	 * @param $csv
	 */
	public function outputCSV($filepath, $csv)
	{
		// Open for writing only; place the file pointer at the end of the file. If the file does not exist, attempt to create it.
		$handle = fopen($filepath, "a");

		// for some reason passing the preset delimiter/enclosure variables results in error
		//$this->delimiter $this->enclosure
		if (fputcsv($handle, $csv, ',', '"') == 0 ) {
			$message = new \Magento\Framework\Phrase('Problem writing CSV file');
			new \Magento\Framework\Exception\FileSystemException($message);
		}

		fclose($handle);

	} // end


	/**
	 * If the path does not exist then create it
	 * @param string $path
	 */
	public function pathExists($path)
	{
		//@todo check the path creation permision rights
		if (!is_dir($path)) {
			mkdir($path, 0777, true);
		} // end

		return;
	} // end


	protected function arrayToCsv( array &$fields, $delimiter, $enclosure, $encloseAll = false, $nullToMysqlNull = false )
	{
		$delimiter_esc = preg_quote($delimiter, '/');
		$enclosure_esc = preg_quote($enclosure, '/');

		$output = array();
		foreach ( $fields as $field ) {
			if ($field === null && $nullToMysqlNull) {
				$output[] = 'NULL';
				continue;
			}

			// Enclose fields containing $delimiter, $enclosure or whitespace
			if ($encloseAll || preg_match( "/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field )) {
				$output[] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure;
			} else {
				$output[] = $field;
			}
		}

		return implode( $delimiter, $output )."\n";
	}

	/**
	 * Delete file or directory
	 * @param $path
	 * @return bool
	 */
	public function deleteDir($path)
	{
		$class_func = array(__CLASS__, __FUNCTION__);
		return is_file($path) ?
			@unlink($path) :
			array_map($class_func, glob($path.'/*')) == @rmdir($path);
	}


	public function getWebsiteCustomerMappingDatafields($website)
	{
		//customer mapped data
		$store = $website->getDefaultStore();
		$mappedData = $this->_scopeConfig->getValue('connector_data_mapping/customer_data', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store->getId());
		unset($mappedData['custom_attributes']);

		foreach ($mappedData as $key => $value) {
			if (! $value)
				unset($mappedData[$key]);
		}

		return $mappedData;
	}



	/**
	 * @param $path
	 *
	 * @return bool
	 */
	public function getPathPermission($path) {

		//check for directory created before looking into permission
		if (is_dir($path)) {
			clearstatcache( null, $path );

			return decoct( fileperms( $path ) & 0777 );
		}
		//the file is not created and return the passing value
		return 755;
	}
}
