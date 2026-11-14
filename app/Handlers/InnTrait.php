<?php

namespace App\Handlers;

trait InnTrait
{
    /**
     * @link https://github.com/feldwebel/yii2-validators-ogrn-inn/blob/master/src/InnValidator.php
     *
     * @param string $inn
     * @return bool
     */
    protected function validateInn(string $inn): bool
    {
        $len = strlen($inn);
        if (($len !== 12) && ($len !== 10)) {
            return false;
        }

        if (
            $len === 10 && ($inn[9] !== (string) (((
                2 * $inn[0] + 4 * $inn[1] + 10 * $inn[2] +
                3 * $inn[3] + 5 * $inn[4] + 9 * $inn[5] +
                4 * $inn[6] + 6 * $inn[7] + 8 * $inn[8]
            ) % 11) % 10))
        ) {
            return false;

        } elseif (
            $len === 12 && (($inn[10] !== (string) (((
                7 * $inn[0] + 2 * $inn[1] + 4 * $inn[2] +
               10 * $inn[3] + 3 * $inn[4] + 5 * $inn[5] +
                9 * $inn[6] + 4 * $inn[7] + 6 * $inn[8] +
                8 * $inn[9]
            ) % 11) % 10) || ($inn[11] !== (string) (((
                3 * $inn[0] + 7 * $inn[1] + 2 * $inn[2] +
                4 * $inn[3] + 10 * $inn[4] + 3 * $inn[5] +
                5 * $inn[6] + 9 * $inn[7] + 4 * $inn[8] +
                6 * $inn[9] + 8 * $inn[10]
            ) % 11) % 10))))
        ) {
            return false;
        }

        return true;
    }
}
