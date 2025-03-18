<?php

namespace App\Traits;

trait ReadonlyPermissions
{
  public static function canCreate(): bool
  {
    return auth()->user()->hasAnyRole([]);
  }

  public static function canEdit($record): bool
  {
    return auth()->user()->hasAnyRole([]);
  }

  public static function canDelete($record): bool
  {
    return auth()->user()->hasAnyRole([]);
  }
}
