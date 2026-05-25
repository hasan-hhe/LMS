<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class MemberService
{
    public function listMembers(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        try {
            $query = User::where('role', 'MEMBER');

            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('identity_number', 'like', "%{$search}%");
                });
            }

            return $query->paginate(15);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getMember(int $id): User
    {
        try {
            $member = User::where('role', 'MEMBER')->find($id);
            if (!$member) {
                throw new \Exception('العضو غير موجود');
            }
            return $member;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function createMember(array $data, $photoFile = null): User
    {
        DB::beginTransaction();
        try {
            $photoUrl = $this->storeProfilePhoto($photoFile);

            $member = User::create([
                'first_name'         => $data['first_name'],
                'last_name'          => $data['last_name'],
                'email'              => $data['email'],
                'phone'              => $data['phone'],
                'identity_number'    => $data['identity_number'],
                'adress'             => $data['adress'] ?? null,
                'role'               => 'MEMBER',
                'photo_url'          => $photoUrl,
                'password_hash'      => Hash::make($data['password']),
                'participe_end_date' => $data['participe_end_date'] ?? null,
            ]);

            DB::commit();
            return $member;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateMember(int $id, array $data, $photoFile = null): User
    {
        DB::beginTransaction();
        try {
            $member = $this->getMember($id);

            if ($photoFile) {
                $this->deleteOldPhoto($member->photo_url);
                $data['photo_url'] = $this->storeProfilePhoto($photoFile);
            }

            $member->update($data);

            DB::commit();
            return $member->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteMember(int $id): void
    {
        DB::beginTransaction();
        try {
            $member = $this->getMember($id);
            $this->deleteOldPhoto($member->photo_url);
            $member->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function storeProfilePhoto($photoFile): ?string
    {
        if (!$photoFile) {
            return null;
        }
        return Storage::disk('public')->putFile('profiles', $photoFile);
    }

    private function deleteOldPhoto(?string $photoUrl): void
    {
        if ($photoUrl && Storage::disk('public')->exists($photoUrl)) {
            Storage::disk('public')->delete($photoUrl);
        }
    }
}
