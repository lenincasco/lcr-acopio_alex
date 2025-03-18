<?php

namespace App\Traits;

trait RegularPermissions
{
  public static function canCreate(): bool
  {
    return auth()->user()->hasAnyRole(['editor', 'admin', 'superadmin']);
  }

  public static function canEdit($record): bool
  {
    return auth()->user()->hasAnyRole(['editor', 'admin', 'superadmin']);
  }

  public static function canDelete($record): bool
  {
    return auth()->user()->hasAnyRole(['superadmin']);
  }
}
