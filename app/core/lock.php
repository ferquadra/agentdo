<?php
class Lock
{
    public static function run($empresa, $fn)
    {
        Storage::assertCode($empresa);
        Storage::ensureDir(Storage::lockDir());
        $file = Storage::lockDir() . '/' . $empresa . '.lock';
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
