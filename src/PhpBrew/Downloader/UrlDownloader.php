<?php
namespace PhpBrew\Downloader;
use Exception;
use RuntimeException;

class UrlDownloader
{
    public $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $url
     *
     * @return bool|string
     *
     * @throws \Exception
     */
    public function download($url, $dir, $basename = NULL)
    {
        $this->logger->info("===> Downloading from $url");

        $basename = $basename ?: $this->resolveDownloadFileName($url);
        if (!$basename) {
            throw new RuntimeException("Can not parse url: $url");
        }

        $targetFilePath = $dir . DIRECTORY_SEPARATOR . $basename;

        // check for wget or curl for downloading the php source archive
        // TODO: use findbin
        if (exec('command -v wget')) {
            system('wget --no-check-certificate -c -O ' . $targetFilePath . ' ' . $url) !== false or die("Download failed.\n");
        } elseif (exec('command -v curl')) {
            system('curl -C - -# -L -o ' . $targetFilePath . ' ' . $url) !== false or die("Download failed.\n");
        } else {
            die("Download failed - neither wget nor curl was found\n");
        }

        // Verify the downloaded file.
        if (!file_exists($targetFilePath)) {
            throw new RuntimeException("Download failed.");
        }
        $this->logger->info("===> $targetFilePath downloaded.");
        return $basename; // return the filename
    }

    /**
     *
     * @param  string         $url
     * @return string|boolean the resolved download file name or false it
     *                            the url string can't be parsed
     */
    protected function resolveDownloadFileName($url)
    {
        // check if the url is for php source archive
        if (preg_match('/php-.+\.tar\.bz2/', $url, $parts)) {
            return $parts[0];
        }

        // try to get the filename through parse_url
        $path = parse_url($url, PHP_URL_PATH);
        if (false === $path || false === strpos($path, ".")) {
            return NULL;
        }
        return basename($path);
    }
}
