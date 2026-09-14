<?php
class Lock
{
    public static function run($empresa, $fn)
    {
        Storage::assertCode($empresa);
        return self::runOnFile(Storage::lockDir() . '/' . $empresa . '.lock', $fn);
    }

    public static function runGlobal($fn)
    {
        return self::runOnFile(Storage::lockDir() . '/_quota.lock', $fn);
    }

    private static function runOnFile($file, $fn)
    {
        Storage::ensureDir(Storage::lockDir());
        Storage::assertLowercasePath($file);

        $fp = fopen($file, 'c+');
        if ($fp === false) {
            throw new RuntimeException('lock_open');
        }

        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            throw new RuntimeException('lock_flock');
        }

        try {
            $result = $fn();
        } catch (Exception $e) {
            flock($fp, LOCK_UN);
            fclose($fp);
            throw $e;
        }

        flock($fp, LOCK_UN);
        fclose($fp);
        return $result;
    }
}
