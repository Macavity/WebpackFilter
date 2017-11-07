<?php
/**
 * WebpackFilter for Assetic
 *
 * @author Alexander Pape <a.pape@paneon.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Macavity\Assetic\Filter;

use Assetic\Asset\AssetInterface;
use Assetic\Filter\BaseNodeFilter;

class WebpackFilter extends BaseNodeFilter
{
    public function __construct($webpackBin = '/usr/bin/webpack', $nodeBin = null)
    {
        $this->webpackBin = $webpackBin;
        $this->nodeBin = $nodeBin;

        $this->addNodePath(dirname(dirname(realpath($webpackBin))));
    }

    /**
     * Filters an asset after it has been loaded.
     *
     * @param AssetInterface $asset An asset
     */
    public function filterLoad(AssetInterface $asset)
    {
        $pb = $this->createProcessBuilder($this->nodeBin
            ? array($this->nodeBin, $this->tscBin)
            : array($this->tscBin));

        if ($sourcePath = $asset->getSourcePath()) {
            $templateName = basename($sourcePath);
        } else {
            $templateName = 'asset';
        }

        $inputDirPath = FilesystemUtils::createThrowAwayDirectory('typescript_in');
        $inputPath = $inputDirPath.DIRECTORY_SEPARATOR.$templateName.'.ts';
        $outputPath = FilesystemUtils::createTemporaryFile('typescript_out');

        file_put_contents($inputPath, $asset->getContent());

        $pb->add($inputPath)->add('--out')->add($outputPath);

        $proc = $pb->getProcess();
        $code = $proc->run();
        unlink($inputPath);
        rmdir($inputDirPath);

        if (0 !== $code) {
            if (file_exists($outputPath)) {
                unlink($outputPath);
            }
            throw FilterException::fromProcess($proc)->setInput($asset->getContent());
        }

        if (!file_exists($outputPath)) {
            throw new \RuntimeException('Error creating output file.');
        }

        $compiledJs = file_get_contents($outputPath);
        unlink($outputPath);

        $asset->setContent($compiledJs);
    }

    /**
     * Filters an asset just before it's dumped.
     *
     * @param AssetInterface $asset An asset
     */
    public function filterDump(AssetInterface $asset)
    {
        // TODO: Implement filterDump() method.
    }
}