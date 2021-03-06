<?php
/**
 * UpdateTrait.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Helpers\Update;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Services\Github\Object\Release;
use FireflyIII\Services\Github\Request\UpdateRequest;
use Log;

/**
 * Trait UpdateTrait
 *
 * @package FireflyIII\Helpers\Update
 */
trait UpdateTrait
{
    /**
     * Get object for the latest release from GitHub.
     *
     * @return Release|null
     */
    public function getLatestRelease(): ?Release
    {
        $return = null;
        /** @var UpdateRequest $request */
        $request = app(UpdateRequest::class);
        try {
            $request->call();
        } catch (FireflyException $e) {
            Log::error(sprintf('Could not check for updates: %s', $e->getMessage()));
        }

        // get releases from array.
        $releases = $request->getReleases();
        if (\count($releases) > 0) {
            // first entry should be the latest entry:
            /** @var Release $first */
            $first  = reset($releases);
            $return = $first;
        }

        return $return;
    }

    /**
     * Parses the version check result in a human readable sentence.
     *
     * @param Release|null $release
     * @param int          $versionCheck
     *
     * @return string
     */
    public function parseResult(Release $release = null, int $versionCheck): string
    {
        $current = (string)config('firefly.version');
        $return  = '';
        if ($versionCheck === -2) {
            $return = (string)trans('firefly.update_check_error');
        }
        if ($versionCheck === -1 && null !== $release) {
            // there is a new FF version!
            // has it been released for at least three days?
            $today       = new Carbon;
            $releaseDate = $release->getUpdated();
            if ($today->diffInDays($releaseDate, true) > 3) {
                $monthAndDayFormat = (string)trans('config.month_and_day');
                $return            = (string)trans(
                    'firefly.update_new_version_alert',
                    [
                        'your_version' => $current,
                        'new_version'  => $release->getTitle(),
                        'date'         => $release->getUpdated()->formatLocalized($monthAndDayFormat),
                    ]
                );
            }
        }

        if (0 === $versionCheck) {
            // you are running the current version!
            $return = (string)trans('firefly.update_current_version_alert', ['version' => $current]);
        }
        if (1 === $versionCheck && null !== $release) {
            // you are running a newer version!
            $return = (string)trans('firefly.update_newer_version_alert', ['your_version' => $current, 'new_version' => $release->getTitle()]);
        }

        return $return;
    }

    /**
     * Compare version and store result.
     *
     * @param Release|null $release
     *
     * @return int
     */
    public function versionCheck(Release $release = null): int
    {
        if (null === $release) {
            return -2;
        }
        $current = (string)config('firefly.version');
        $latest  = $release->getTitle();
        $check   = version_compare($current, $latest);
        Log::debug(sprintf('Comparing %s with %s, result is %s', $current, $latest, $check));

        return $check;
    }
}