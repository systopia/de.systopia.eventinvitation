<?php

declare(strict_types = 1);

/*-------------------------------------------------------+
| SYSTOPIA Event Invitation                              |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: B. Zschiedrich (zschiedrich@systopia.de)       |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*/

/**
 * Base class for all "object classes" that function as data holders. \
 * This provides basic functionality like converting from and to arrays.
 */
// phpcs:ignore Generic.NamingConventions.AbstractClassNamePrefix.Missing
abstract class CRM_Eventinvitation_Object_BaseClass {

  /**
   * NOTE: Base classes that contain objects as members must convert them manually!
   * @param array<string, mixed> $array The given array will be used to initialise the new object.
   */
  public function __construct(array $array = []) {
    foreach ($array as $key => $value) {
      // @phpstan-ignore-next-line property.dynamicName
      $this->$key = $value;
    }
  }

  /**
   * Convert the object to an array. The object itself will be untouched. \
   * NOTE: Private and protected members will be included in the converted arrays.
   * @return array<string, mixed> The converted object as array.
   */
  public function toArray(): array {
    $array = [];

    foreach (get_object_vars($this) as $key => $value) {
      if ($value instanceof self) {
        $value = $value->toArray();
      }
      elseif (is_object($value)) {
        throw new TypeError(
        'For converting to an array all object members must be of a child class of CRM_Onlyoffice_Object_BaseClass.'
          );
      }

      $array[$key] = $value;
    }

    return $array;
  }

}
