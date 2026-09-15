import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';

class CustomCheckbox extends StatelessWidget {
  final RxBool value;
  final ValueChanged<bool?>? onChanged;

  const CustomCheckbox({
    super.key,
    required this.value,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Obx(() {
      return Checkbox(
        value: value.value,
        onChanged: (checked) {
          value.value = checked!;
          if (onChanged != null) {
            onChanged!(checked);
          }
        },
        activeColor: Theme.of(context).primaryColor,
        checkColor: value.value ? Theme.of(context).colorScheme.surface : null,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(Dimensions.radius * 0.5),
        ),
        side: BorderSide(
          color: Theme.of(context).brightness == Brightness.dark
              ? CustomColor.primaryDarkTextColor.withValues(alpha: 0.50)
              : CustomColor.blackColor.withValues(alpha: 0.40),
        ),
      );
    });
  }
}
