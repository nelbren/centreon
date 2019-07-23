<?php
namespace CentreonRemote\Infrastructure\Export;

use CentreonRemote\Infrastructure\Export\ExportCommitment;
use DateTime;
use Exception;

class ExportManifest
{

    const EXPORT_FILE = 'manifest.json';
    const ERR_CODE_MANIFEST_NOT_FOUND = 1001;
    const ERR_CODE_MANIFEST_WRONG_FORMAT = 1002;
    const ERR_CODE_INCOMPATIBLE_VERSIONS = 1005;

    /**
     * @var \CentreonRemote\Infrastructure\Export\ExportCommitment
     */
    private $commitment;

    /**
     * @var string
     */
    private $version;

    /**
     * @var array
     */
    private $data;

    public function __construct(ExportCommitment $commitment, string $version = null)
    {
        $this->commitment = $commitment;
        $this->version = $version;
    }

    public function get(string $key)
    {
        $result = $this->data && array_key_exists($key, $this->data) ? $this->data[$key] : null;

        return $result;
    }

    public function validate()
    {
        $file = $this->getFile();

        if (!file_exists($file)) {
            throw new Exception(sprintf('Manifest file %s not found', $file), static::ERR_CODE_MANIFEST_NOT_FOUND);
        }

        $this->data = $this->commitment->getParser()->parse($file);
        
        $checkManifestKeys = function (array $data) : array {
            $keys = ['date', 'remote_server', 'pollers', 'import'];
            $missingKeys = [];
            
            foreach ($keys as $key) {
                if (!array_key_exists($key, $data)) {
                    $missingKeys[] = $key;
                }
            }
            
            return $missingKeys;
        };

        if ($missingKeys = $checkManifestKeys($this->data)) {
            throw new Exception(sprintf("Missing data in a manifest file:\n - %s", join("\n - ", $missingKeys)), static::ERR_CODE_MANIFEST_WRONG_FORMAT);
        }

        # Compare only the major and minor version, not bugfix because no SQL schema changes
        $centralVersion = preg_replace('/^(\d+\.\d+).*/', '$1', $this->data['version']);
        $remoteVersion = preg_replace('/^(\d+\.\d+).*/', '$1', $this->version);

        if (!version_compare($centralVersion, $remoteVersion, '==')) {
            throw new Exception(
                sprintf(
                    'The version of the Central %s and of the Remote %s are incompatible',
                    $this->data['version'],
                    $this->version
                ),
                static::ERR_CODE_INCOMPATIBLE_VERSIONS
            );
        }

        return $this->data;
    }

    public function dump(array $exportManifest): void
    {
        $data = array_merge($exportManifest, [ "version" => $this->version ]);

        $this->commitment->getParser()->dump($data, $this->getFile());
    }

    public function getFile(): string
    {
        $file = $this->commitment->getPath() . '/' . static::EXPORT_FILE;

        return $file;
    }
}
