<?php

namespace App\Policies;

use App\Models\{User, VendorProduct};

class VendorProductPolicy
{
    public function update(User $user, VendorProduct $vendorProduct): bool
    {
        return $user->id === $vendorProduct->vendor_id;
    }

    public function delete(User $user, VendorProduct $vendorProduct): bool
    {
        return $user->id === $vendorProduct->vendor_id;
    }
}
