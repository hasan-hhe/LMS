<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class LibrarianService
{
    public function listLibrarians(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        try {
            $query = User::where('role', 'LIBRARIAN');

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

    public function getLibrarian(int $id): User
    {
        try {
            $librarian = User::where('role', 'LIBRARIAN')->find($id);
            if (!$librarian) {
                throw new \Exception('أمين المكتبة غير موجود');
            }
            return $librarian;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function createLibrarian(array $data, $photoFile = null): User
    {
        DB::beginTransaction();
        try {
            $photoUrl = $this->storeProfilePhoto($photoFile);

            $librarian = User::create([
                'first_name'      => $data['first_name'],
                'last_name'       => $data['last_name'],
                'email'           => $data['email'],
                'phone'           => $data['phone'],
                'identity_number' => $data['identity_number'],
                'adress'          => $data['adress'] ?? null,
                'role'            => 'LIBRARIAN',
                'photo_url'       => $photoUrl,
                'password_hash'   => Hash::make($data['password']),
            ]);

            DB::commit();
            return $librarian;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateLibrarian(int $id, array $data, $photoFile = null): User
    {
        DB::beginTransaction();
        try {
            $librarian = $this->getLibrarian($id);

            if ($photoFile) {
                $this->deleteOldPhoto($librarian->photo_url);
                $data['photo_url'] = $this->storeProfilePhoto($photoFile);
            }

            $librarian->update($data);

            DB::commit();
            return $librarian->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteLibrarian(int $id): void
    {
        DB::beginTransaction();
        try {
            $librarian = $this->getLibrarian($id);

            if ($librarian->librarianBorrowings()->exists()) {
                throw new \Exception('لا يمكن حذف أمين المكتبة لوجود استعارات مرتبطة به');
            }

            $this->deleteOldPhoto($librarian->photo_url);
            $librarian->delete();

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
