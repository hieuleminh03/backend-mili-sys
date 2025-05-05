<?php

namespace App\Services;

use App\Models\ManagerDetail;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class ManagerService
{
    /**
     * Lấy danh sách các manager
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllManagers()
    {
        try {
            $managers = User::select(
                'users.id', 
                'users.name', 
                'users.email', 
                'users.image',
                'manager_details.rank',
                'manager_details.phone_number as phone'
            )
                ->leftJoin('manager_details', 'users.id', '=', 'manager_details.user_id')
                ->where('role', User::ROLE_MANAGER)
                ->orderBy('name')
                ->get();

            return $managers;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Lấy thông tin chi tiết của một manager
     *
     * @return array
     *
     * @throws Exception
     */
    public function getManagerDetail(int $managerId)
    {
        try {
            $manager = User::where('id', $managerId)
                ->where('role', User::ROLE_MANAGER)
                ->first();

            if (! $manager) {
                throw new Exception('Không tìm thấy manager', 422);
            }

            // Lấy thông tin chi tiết, nếu chưa có thì tạo mới
            $managerDetail = $manager->managerDetail;

            if (! $managerDetail) {
                $managerDetail = ManagerDetail::create([
                    'user_id' => $managerId,
                ]);
            }

            // Ẩn trường id từ manager_detail
            $detailArray = $managerDetail->toArray();
            unset($detailArray['id']);

            // Gộp thông tin manager và chi tiết vào một đối tượng
            $result = [
                'id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
                'image' => $manager->image,
                'detail' => $detailArray,
            ];

            return $result;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Cập nhật thông tin chi tiết của manager
     *
     * @return array
     *
     * @throws Exception
     */
    public function updateManagerDetail(int $managerId, array $data)
    {
        try {
            return DB::transaction(function () use ($managerId, $data) {
                $manager = User::where('id', $managerId)
                    ->where('role', User::ROLE_MANAGER)
                    ->first();

                if (! $manager) {
                    throw new Exception('Không tìm thấy manager', 422);
                }
                
                // Xử lý cập nhật ảnh nếu có
                if (isset($data['image'])) {
                    $manager->image = $data['image'];
                    $manager->save();
                    
                    // Loại bỏ trường image ra khỏi dữ liệu chi tiết
                    unset($data['image']);
                }

                // Lấy thông tin chi tiết, nếu chưa có thì tạo mới
                $managerDetail = $manager->managerDetail;

                if (! $managerDetail) {
                    $managerDetail = new ManagerDetail;
                    $managerDetail->user_id = $managerId;
                }

                // Cập nhật thông tin chi tiết
                $managerDetail->fill($data);
                $managerDetail->save();

                // Ẩn trường id từ manager_detail
                $detailArray = $managerDetail->toArray();
                unset($detailArray['id']);
                
                // Đảm bảo lấy thông tin user đã được cập nhật mới nhất
                $manager = $manager->fresh();
                
                // Gộp thông tin manager và chi tiết vào một đối tượng
                $result = [
                    'id' => $manager->id,
                    'name' => $manager->name,
                    'email' => $manager->email,
                    'image' => $manager->image,
                    'detail' => $detailArray,
                ];

                return $result;
            });
        } catch (Exception $e) {
            throw $e;
        }
    }
}
