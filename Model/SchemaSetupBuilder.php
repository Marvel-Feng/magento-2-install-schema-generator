<?php
/**
 * Blackbird InstallSchemaGenerator Module
 *
 * NOTICE OF LICENSE
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contact@bird.eu so we can send you a copy immediately.
 *
 * @category        Blackbird
 * @package         Blackbird_InstallSchemaGenerator
 * @copyright       Copyright (c) 2018 Blackbird (https://black.bird.eu)
 * @author          Blackbird Team
 * @license         MIT LICENSE
 */
namespace Blackbird\InstallSchemaGenerator\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\View\Element\BlockFactory;
use Blackbird\InstallSchemaGenerator\Api\SchemaSetupBuilderInterface;
use Blackbird\InstallSchemaGenerator\Model\DB\SchemaRetriever;
use Blackbird\InstallSchemaGenerator\Block\InstallSchema;

/**
 * Class SchemaSetupBuilder
 */
class SchemaSetupBuilder implements SchemaSetupBuilderInterface
{
    /**
     * Default namespace name
     */
    const DEFAULT_NAMESPACE = 'Vendor\Area';
    
    /**
     * Default filename for the setup file
     */
    const DEFAULT_FILENAME = 'install-schema-generator/InstallSchema.php';

    /**
     * @var SchemaRetriever
     */
    private $schemaRetriever;
    
    /**
     * @var Filesystem
     */
    private $filesystem;
    
    /**
     * @var BlockFactory 
     */
    private $blockFactory;

    /**
     * @param SchemaRetriever $schemaRetriever
     * @param Filesystem $filesystem
     * @param BlockFactory $blockFactory
     */
    public function __construct(
        SchemaRetriever $schemaRetriever,
        Filesystem $filesystem,
        BlockFactory $blockFactory
    ) {
        $this->schemaRetriever = $schemaRetriever;
        $this->filesystem = $filesystem;
        $this->blockFactory = $blockFactory;
    }
    
    /**
     * {@inheritdoc}
     */
    public function generate(
        array $tables = [],
        $namespace = self::DEFAULT_NAMESPACE,
        $filename = self::DEFAULT_FILENAME
    ) {
        // Generate the renderer template block
        $block = $this->blockFactory->createBlock(InstallSchema::class)
            ->setNamespace($this->sanitizeNamespace($namespace))
            ->setTables($this->schemaRetriever->getSchema($tables));

        // Create the InstallSchema.php class file
        $writer = $this->filesystem->getDirectoryWrite(DirectoryList::TMP);

        $file = $writer->openFile($filename, 'w');

        try {
            $file->lock();
            $file->write($block->getHtml());
        } catch (\Exception $e) {
            throw new LocalizedException(__('An error has occurred during the generation of the %1 setup file.', $filename));
        } finally {
            $file->unlock();
            $file->close();
        }

        return $filename;
    }
    
    /**
     * Filter and sanitize the namespace input
     * 
     * @param string $namespace
     * @return string
     */
    private function sanitizeNamespace($namespace)
    {
        return $this->isNamespace($namespace) ? $namespace : self::DEFAULT_NAMESPACE;
    }
    
    /**
     * If pattern is a part of namespace, then return it, else return false
     * 
     * @param string $namespace
     * @return boolean|string
     */
    private function isNamespace($namespace)
    {
        return (!empty($namespace) && count(explode("\\", $namespace)) === 2);
    }
}
