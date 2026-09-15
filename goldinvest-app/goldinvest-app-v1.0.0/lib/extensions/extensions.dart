import 'package:flutter/material.dart';
import 'package:get/get.dart';

extension SupperRoute on String {
  Future? get toNamed => Get.toNamed(this);
  Future? get offAllNamed => Get.offAllNamed(this);
  Future? get offNamed => Get.offNamed(this);
}

extension SupperEdgeInsets on dynamic {
  // EdgeInsets
  EdgeInsets get edgeHorizontal => EdgeInsets.symmetric(horizontal: this);
  EdgeInsets get edgeVertical => EdgeInsets.symmetric(vertical: this);
  EdgeInsets get edgeTop => EdgeInsets.only(top: this);
  EdgeInsets get edgeBottom => EdgeInsets.only(bottom: this);
  EdgeInsets get edgeLeft => EdgeInsets.only(left: this);
  EdgeInsets get edgeRight => EdgeInsets.only(right: this);

  /// Radius
  BorderRadius get radiusEx => BorderRadius.circular(this);
  BorderRadius get radiusTopEx => BorderRadius.only(
        topLeft: Radius.circular(this),
        topRight: Radius.circular(this),
      );
}
