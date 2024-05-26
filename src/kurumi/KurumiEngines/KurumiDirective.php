<?php

namespace Kurumi\KurumiEngines;


use Exception;


/**
 *
 *  Class KurumiDirective yang bertanggung jawab 
 *  atas semua hal yang mengenai directive.
 *
 *  @author Lutfi Aulia Sidik
 **/
final class KurumiDirective extends KurumiEngine implements KurumiDirectiveInterface
{

    use Traits\CompilerLayouts;


    /**
     * 
     *  Menyimpan directory input.
     *
     *  @property string $directoryInput
     **/
    private string $directoryInput = "";

    
    /**
     * 
     *  Menyimpan directory output.
     *
     *  @property string $directoryOutput
     **/
    private string $directoryOutput = "";


    /**
     *  
     *  Menyimpan $directive.
     *
     *  @property array $directive
     **/
    protected array $directive = [];


    /**
     *
     * 
     **/
    protected array $footer = [];



    /**
     *  
     *  Jalankan method yang perlu dijalankan saat 
     *  pertamakali class dipanggil.
     *
     **/
    public function __construct()
    {
        $this->addDefaultDirectives();
        $this->compiledKurumiExtends();
    }


    
    /**
     * 
     *  Set directory input dan output.
     *
     *  @param string $input
     *  @param string $output
     *  @return object 
     **/
    public function setDirectory(string $input, string $output): object
    {
        $this->directoryInput  = $input;
        $this->directoryOutput = $output;

        return $this;
    }



    /**
     *  
     *  Load file yang akan digenerate dan 
     *  kembalikan hasilnya.
     *  
     *  @param string $path
     *  @return string 
     **/
    protected function getFileContent(string $path): string
    {
        $pathFile = $this->directoryInput . $path . parent::DEFAULT_FILE_EXTENSION;

        return parent::getFileContent($pathFile);
    }



    /**
     *
     *  Validasi folder input dan output jika 
     *  folder output tidak ditemukan maka buat
     *
     *  @throw \Exception jika folder input tidak ditemukan.
     *  @throw \Exception jika folder output tidak ditemukan.
     *  @return void 
     **/
    private function validateDirectory(): void
    {
        if (!file_exists($this->directoryInput) || !is_writable($this->directoryInput)) {
            throw new Exception("Direktori input tidak valid: {$this->directoryInput}");
        } elseif (@$this->directoryOutput[-1] !== "/") {
            throw new Exception("Direktori output tidak valid: {$this->directoryOutput}");
        } elseif (!file_exists($this->directoryOutput)) {
            mkdir($this->directoryOutput, 0777, true);
        }
    }



    /**
     * 
     *  Menambahkan directive baru.
     *
     *  @param string $pattern
     *  @param string $replacement
     *  @return void 
     **/
    public function addDirective(string $pattern, string $replacement): void
    {
        $this->directive[$pattern] = $replacement;
    }


 
    /**
     * 
     *  Menambahkan directive baru dengan array.
     *
     *  @param array $directives
     *  @return void
     **/
    public function addDirectiveAll(array $directives): void
    {
        if(!is_null($directives) and sizeof($directives) > 0) {
            foreach($directives as $pattern => $replacement) {
                $this->directive[$pattern] = $replacement;
            }
        }
    }



    /**
     *  
     *  Tambahkan default directive 
     *  @return void 
     **/
    private function addDefaultDirectives(): void
    {

        $this->addDirectiveAll([
            '/{{\s*(.*?)\s*}}/' =>'<?php echo htmlspecialchars($1) ?>',
            '/{!\s*(.*?)\s*!}/' =>'<?php echo $1 ?>',
            '/@kurumiforeach\s*\((.*?)\)(.*?)\s*@endkurumiforeach/s' => '<?php foreach($1): ?>$2<?php endforeach; ?>',
            '/@kurumiphp\s*(.*?)\s*@endkurumiphp/s' => '<?php $1 ?>',
            '/@kurumiExtends\s*\((.*)\)\s*/' => '<?php $__temp->extendContent($1) ?>',
            '/@kurumiSection\s*\((.*?)\)(.*?)\s*@endkurumisection/s' => '<?php $__temp->startContent($1) ?>$2<?php $__temp->stopContent(); ?>',
            '/@kurumiSection\s*\((.*)\)\s*/' =>'<?php $__temp->startContent($1) ?>',
            '/@kurumiContent\s*\((.*)\)\s*/' => '<?php $__temp->content($1) ?>',
            '/@kurumiInclude\s*\((.*)\)\s*/' =>'<?php $__temp->includeFile($1) ?>',
            '/@kurumiImport\s*\((.*)\)\s*/' =>'<?php $__temp->importFile($__view, $1) ?>',
            '/@oppai\s*\((.*)\)\s*/' => '<?php oppai($1); ?>',
            '/^\s*[\r\n]+/m' => '',
            //'/[\r\n]+/' => ''
        ]);
    }



    /**
     * 
     *  Mengubah syntax directive menjadi syntax php biasa,
     *  dan kembalikan hasilnya.
     * 
     *  @param string $content 
     *  @return string 
     **/
    private function processesDirectives(string $content): string
    {
        foreach ($this->directive as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }



    /**
     *
     * 
     *
     **/
    protected function compiledKurumiExtends()
    {
        preg_replace(
            pattern: '/@kurumiExtends\s*\((.*)\)\s*/',
            replacement: $this->compileKurumiExtends(),
            subject: ''
        );
    }



    /**
     * 
     *  Render, Generate file baru dengan hasil
     *  convert directive.
     *
     *  @param string $view 
     *  @return void 
     **/
    public function compile(string $view): void
    {
        $this->validateDirectory();

        $fileContent   = $this->getFileContent($view);
        $resultContent = $this->processesDirectives($fileContent);
        $pathFileInput = $this->directoryInput . $view . parent::DEFAULT_FILE_EXTENSION;
        $pathGenerateOutput = $this->directoryOutput . pathToDot($view) . '.php';
        

        // saat pertamakali compile dijalankan,
        // selanjutnya compile akan dijalankan jika
        // terdapat perubahan difile input saja.
        if (!file_exists($pathGenerateOutput)) {
            file_put_contents($pathGenerateOutput, $resultContent); 
        } elseif (isFileUpdate($pathFileInput, $pathGenerateOutput)) {
            file_put_contents($pathGenerateOutput, $resultContent); 
        }
    }
}
