<?php
/**
 * DeleteController.php
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

namespace FireflyIII\Http\Controllers\Recurring;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Recurrence;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use Illuminate\Http\Request;

/**
 * Class DeleteController
 */
class DeleteController extends Controller
{
    /** @var RecurringRepositoryInterface */
    private $recurring;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-paint-brush');
                app('view')->share('title', trans('firefly.recurrences'));

                $this->recurring = app(RecurringRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param Recurrence $recurrence
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function delete(Recurrence $recurrence)
    {
        $subTitle = trans('firefly.delete_recurring', ['title' => $recurrence->title]);
        // put previous url in session
        $this->rememberPreviousUri('recurrences.delete.uri');

        // todo actual number.
        $journalsCreated = $this->recurring->getTransactions($recurrence)->count();

        return view('recurring.delete', compact('recurrence', 'subTitle', 'journalsCreated'));
    }

    /**
     * @param RecurringRepositoryInterface $repository
     * @param Request                      $request
     * @param Recurrence                   $recurrence
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(RecurringRepositoryInterface $repository, Request $request, Recurrence $recurrence)
    {
        $repository->destroy($recurrence);
        $request->session()->flash('success', (string)trans('firefly.' . 'recurrence_deleted', ['title' => $recurrence->title]));
        app('preferences')->mark();

        return redirect($this->getPreviousUri('recurrences.delete.uri'));
    }

}