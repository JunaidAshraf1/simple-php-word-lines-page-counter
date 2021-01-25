<?php
include('class.pdf2text.php');
class DocText{
    private $filename;

    public function __construct($filePath) {
        $this->filename = $filePath;
    }

    private function read_doc() {
        $fileHandle = fopen($this->filename, "r");
        $line = @fread($fileHandle, filesize($this->filename));   
        $lines = explode(chr(0x0D),$line);
        $outtext = "";
        foreach($lines as $thisline)
          {
            $pos = strpos($thisline, chr(0x00));
            if (($pos !== FALSE)||(strlen($thisline)==0))
              {
              } else {
                $outtext .= $thisline." ";
              }
          }
         $outtext = preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/","",$outtext);
        return $outtext;
    }

    private function read_docx(){

        $striped_content = '';
        $content = '';

        $zip = zip_open($this->filename);

        if (!$zip || is_numeric($zip)) return false;

        while ($zip_entry = zip_read($zip)) {

            if (zip_entry_open($zip, $zip_entry) == FALSE) continue;

            if (zip_entry_name($zip_entry) != "word/document.xml") continue;

            $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

            zip_entry_close($zip_entry);
        }// end while

        zip_close($zip);

        $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
        $content = str_replace('</w:r></w:p>', "\r\n", $content);
        $striped_content = strip_tags($content);

        return $striped_content;
    }

 /************************excel sheet************************************/

function xlsx_to_text($input_file){
    $xml_filename = "xl/sharedStrings.xml"; //content file name
    $zip_handle = new ZipArchive;
    $output_text = "";
    if(true === $zip_handle->open($input_file)){
        if(($xml_index = $zip_handle->locateName($xml_filename)) !== false){
            $xml_datas = $zip_handle->getFromIndex($xml_index);
            $xml_handle = DOMDocument::loadXML($xml_datas, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
            $output_text = strip_tags($xml_handle->saveXML());
        }else{
            $output_text .="";
        }
        $zip_handle->close();
    }else{
    $output_text .="";
    }
    return $output_text;
}

/*************************power point files*****************************/
function pptx_to_text($input_file){
    $zip_handle = new ZipArchive;
    $output_text = "";
    if(true === $zip_handle->open($input_file)){
        $slide_number = 1; //loop through slide files
        while(($xml_index = $zip_handle->locateName("ppt/slides/slide".$slide_number.".xml")) !== false){
            $xml_datas = $zip_handle->getFromIndex($xml_index);
            $xml_handle = DOMDocument::loadXML($xml_datas, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
            $output_text .= strip_tags($xml_handle->saveXML());
            $slide_number++;
        }
        if($slide_number == 1){
            $output_text .="";
        }
        $zip_handle->close();
    }else{
    $output_text .="";
    }
    return $output_text;
}


    public function convertToText() {

        if(isset($this->filename) && !file_exists($this->filename)) {
            return "File Not exists";
        }

        $fileArray = pathinfo($this->filename);
        $file_ext  = $fileArray['extension'];
        if($file_ext == "doc" || $file_ext == "docx" || $file_ext == "xlsx" || $file_ext == "pptx")
        {
            if($file_ext == "doc") {
                return $this->read_doc();
            } elseif($file_ext == "docx") {
                return $this->read_docx();
            } elseif($file_ext == "xlsx") {
                return $this->xlsx_to_text();
            }elseif($file_ext == "pptx") {
                return $this->pptx_to_text();
            }
        } else {
            return "Invalid File Type";
        }
    }

}


class DocCounter {
    
    // Class Variables   
    private $file;
    private $filetype;
    
    // Set file
    public function setFile($filename)
    {
        $this->file = $filename;
        $this->filetype = pathinfo($this->file, PATHINFO_EXTENSION);
    }
    
    // Get file
    public function getFile()
    {
        return $this->file;
    }
    
    // Get file information object
    public function getInfo()
    {
        // Function variables
        $ft = $this->filetype;
        
        // Let's construct our info response object
        $obj = new stdClass();
        $obj->format = $ft;
        $obj->wordCount = null;
        $obj->lineCount = null;
        $obj->pageCount = null;
        
        // Let's set our function calls based on filetype
        switch($ft)
        {
            case "doc":
                $doc = $this->read_doc_file();
                $obj->wordCount = $this->str_word_count_utf8($doc);
                $obj->lineCount = $this->lineCount($doc);
                $obj->pageCount = $this->pageCount($doc);
                break;
            case "docx":
                $docObj = new DocText($this->file);
                $docText= $docObj->convertToText();
                $string = preg_replace('/\s+/', ' ', trim($docText));
                $words = explode(" ", $string);
                $obj->wordCount =  count($words);
                // $obj->wordCount = $this->str_word_count_utf8($this->docx2text());
                $obj->lineCount = $this->lineCount($this->docx2text());
                $obj->pageCount = $this->PageCount_DOCX();
                break;
            case "pdf":
                $obj->wordCount = $this->str_word_count_utf8($this->pdf2text());
                $obj->lineCount = $this->lineCount($this->pdf2text());
                $obj->pageCount = $this->PageCount_PDF();
                break;
            case "txt":
                $textContents = file_get_contents($this->file);
                $obj->wordCount = $this->str_word_count_utf8($textContents);
                $obj->lineCount = $this->lineCount($textContents);
                $obj->pageCount = $this->pageCount($textContents);
                break;
            default:
                $obj->wordCount = "unsupported file format";
                $obj->lineCount = "unsupported file format";
                $obj->pageCount = "unsupported file format";
        }
        
        return $obj;
    }
    
    // Convert: Word.doc to Text String
    function read_doc_file() {
        
        $path = getcwd();
        $f = $path."/".$this->file;
         if(file_exists($f))
        {
            if(($fh = fopen($f, 'r')) !== false ) 
            {
               $headers = fread($fh, 0xA00);

               // 1 = (ord(n)*1) ; Document has from 0 to 255 characters
               $n1 = ( ord($headers[0x21C]) - 1 );

               // 1 = ((ord(n)-8)*256) ; Document has from 256 to 63743 characters
               $n2 = ( ( ord($headers[0x21D]) - 8 ) * 256 );

               // 1 = ((ord(n)*256)*256) ; Document has from 63744 to 16775423 characters
               $n3 = ( ( ord($headers[0x21E]) * 256 ) * 256 );

               // 1 = (((ord(n)*256)*256)*256) ; Document has from 16775424 to 4294965504 characters
               $n4 = ( ( ( ord($headers[0x21F]) * 256 ) * 256 ) * 256 );

               // Total length of text in the document
               $textLength = ($n1 + $n2 + $n3 + $n4);

               $extracted_plaintext = fread($fh, $textLength);
                $extracted_plaintext = mb_convert_encoding($extracted_plaintext,'UTF-8');
               // simple print character stream without new lines
               //echo $extracted_plaintext;

               // if you want to see your paragraphs in a new line, do this
               return nl2br($extracted_plaintext);
               // need more spacing after each paragraph use another nl2br
            }
        }
    }
    // Jonny 5's simple word splitter
    function str_word_count_utf8($str) {
        return count(preg_split('~[^\p{L}\p{N}\']+~u',$str));
    }
    // Convert: Word.docx to Text String
    function docx2text()
    {
        return $this->readZippedXML($this->file, "word/document.xml");
    }

    function readZippedXML($archiveFile, $dataFile)
    {
        // Create new ZIP archive
        $zip = new ZipArchive;
        
        // set absolute path
        $path = getcwd();
        $f = $path."/".$archiveFile;

        // Open received archive file
        if (true === $zip->open($f)) {
            // If done, search for the data file in the archive
            if (($index = $zip->locateName($dataFile)) !== false) {
                // If found, read it to the string
                $data = $zip->getFromIndex($index);
                // Close archive file
                $zip->close();
                // Load XML from a string
                // Skip errors and warnings
                $xml = new DOMDocument();
                $xml->loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
                
                $xmldata = $xml->saveXML();
                // Newline Replacement
                $xmldata = str_replace("</w:p>", "\r\n", $xmldata);
                // Return data without XML formatting tags
                return strip_tags($xmldata);
            }
            $zip->close();
        }

        // In case of failure return empty string
        return "";
    }
    
    // Convert: Word.doc to Text String
    function read_doc()
    {
        $path = getcwd();
        $f = $path."/".$this->file;
        $fileHandle = fopen($f, "r");
        $line = @fread($fileHandle, filesize($this->file));   
        $lines = explode(chr(0x0D),$line);
        $outtext = "";
        foreach($lines as $thisline)
          {
            $pos = strpos($thisline, chr(0x00));
            if (($pos !== FALSE)||(strlen($thisline)==0))
              {
              } else {
                $outtext .= $thisline." ";
              }
          }
        $outtext = preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/","",$outtext);
        return $outtext;
    }
    
    // Convert: Adobe.pdf to Text String
    function pdf2text()
    {
        //absolute path for file
        $path = getcwd();
        $f = $path."/".$this->file;
        
        if (file_exists($f)) {
            $a = new PDF2Text();
            $a->setFilename($f); 
            $a->decodePDF();
            $text = $a->output();
            return $text;
        }
        
        return null;
    }
    
    // Page Count: DOCX using XML Metadata
    function PageCount_DOCX()
    {
        $pageCount = 0;

        $zip = new ZipArchive();
        
        $path = getcwd();
        $f = $path."/".$this->file;

        if($zip->open($f) === true) {
            if(($index = $zip->locateName('docProps/app.xml')) !== false)  {
                $data = $zip->getFromIndex($index);
                $zip->close();
                $xml = new SimpleXMLElement($data);
                $pageCount = $xml->Pages;
            }
        }

        return intval($pageCount);
    }

    // Page Count: PDF using FPDF and FPDI 
    function PageCount_PDF()
    {
        //absolute path for file
        $path = getcwd();
        $f = $path."/".$this->file;
         if (!$fp = fopen($f, 'r')) {
            echo 'failed opening file '.$f;
            }
            else {
            $max=0;
            while(!feof($fp)) {
            $line = fgets($fp,255);
            if (preg_match('/\/Count [0-9]+/', $line, $matches)){
            preg_match('/[0-9]+/',$matches[0], $matches2);
            if ($max<$matches2[0]) $max=$matches2[0];
            }
            }
            fclose($fp);
            //echo 'There '.($max<2?'is ':'are ').$max.' page'.($max<2?'':'s').' in '. $f.'.';
            }
        return $pageCount = $max;
    }
    
    // Page Count: General
    function pageCount($text)
    {   
        require_once('lib/fpdf/fpdf.php');

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Times','',12);
        $pdf->MultiCell(0,5,$text);
        //$pdf->Output();
        $filename="tmp.pdf";
        $pdf->Output($filename,'F');
        
        require_once('lib/fpdi/fpdi.php');
        $pdf = new FPDI();
        $pageCount = $pdf->setSourceFile($filename);
        
        unlink($filename);
        return $pageCount;
    }
    
    // Line Count: General
    function lineCount($text)
    {
        $lines_arr = preg_split('/\n|\r/',$text);
        $num_newlines = count($lines_arr); 
        return $num_newlines;
    }
}


?>
