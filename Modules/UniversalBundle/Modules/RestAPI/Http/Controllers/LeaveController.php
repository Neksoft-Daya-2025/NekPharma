<?php

namespace Modules\RestAPI\Http\Controllers;

use App\Models\LeaveType;
use App\Models\User;
use Froiden\RestAPI\Exceptions\ApiException;
use Modules\RestAPI\Entities\Leave;
use Modules\RestAPI\Http\Requests\Leave\CreateRequest;
use Modules\RestAPI\Http\Requests\Leave\DeleteRequest;
use Modules\RestAPI\Http\Requests\Leave\IndexRequest;
use Modules\RestAPI\Http\Requests\Leave\ShowRequest;
use Modules\RestAPI\Http\Requests\Leave\UpdateRequest;

class LeaveController extends ApiBaseController
{
    protected $model = Leave::class;

    protected $indexRequest = IndexRequest::class;

    protected $storeRequest = CreateRequest::class;

    protected $updateRequest = UpdateRequest::class;

    protected $showRequest = ShowRequest::class;

    protected $deleteRequest = DeleteRequest::class;

    public function modifyIndex($query)
    {
        return $query->visibility()
            ->join(
                \DB::raw('(SELECT `id` as `a_user_id`, `name` as `employee_name` FROM `users`) as `a`'),
                'a.a_user_id',
                '=',
                'leaves.user_id'
            );
    }

    /**
     * Hook called by ApiController::store() before saving.
     * Enforces the same probation restriction as the web LeaveController.
     */
    public function storing($leave)
    {
        $typeId = request('type.id') ?? request('leave_type_id');
        $userId = request('user.id') ?? request('user_id') ?? (api_user()->id ?? null);

        if ($typeId && $userId) {
            $leaveType = LeaveType::find($typeId);
            $user      = User::with(['roles', 'employeeDetail'])->find($userId);

            if ($leaveType && $user) {
                $leaveTypeModel = new LeaveType();

                if (!$leaveTypeModel->leaveTypeCondition($leaveType, $user)) {
                    throw new ApiException(
                        422,
                        __('messages.leaveTypeNotAllowed')
                            ?: 'This leave type is not allowed during your probation period.'
                    );
                }
            }
        }

        return $leave;
    }
}
