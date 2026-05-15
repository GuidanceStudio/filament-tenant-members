<?php

namespace Guidance\FilamentTenantMembers\Models;

use Guidance\FilamentTenantMembers\Concerns\IsOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug'])]
class Organization extends Model
{
    use IsOrganization;
}
