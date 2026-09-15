import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:dropdown_button2/dropdown_button2.dart';

import '../../utils/custom_color.dart';
import '../../utils/custom_style.dart';
import '../../utils/dimensions.dart';

class KycDynamicDropDown extends StatelessWidget {
  final RxString selectMethod;
  final String label;
  final List<String> itemsList;
  final void Function(String?)? onChanged;

  const KycDynamicDropDown({
    required this.itemsList,
    super.key,
    required this.selectMethod,
    this.onChanged,
    required this.label,
  });

  @override
  Widget build(BuildContext context) {
    return Obx(() => Padding(
          padding: EdgeInsets.only(
              left: Dimensions.paddingSize, right: Dimensions.paddingSize),
          child: Container(
            height: Dimensions.inputBoxHeight * .80,
            decoration: BoxDecoration(
              color: CustomColor.blackColor.withValues(alpha: 0.05),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Padding(
              padding: const EdgeInsets.only(left: 30, right: 20),
              child: DropdownButtonHideUnderline(
                child: DropdownButton2(
                  hint: Padding(
                    padding:
                        EdgeInsets.only(left: Dimensions.paddingSize * 0.7),
                    child: Text(
                      selectMethod.value,
                      style: CustomStyle.darkHeading3TextStyle.copyWith(
                        color: CustomColor.primaryLightTextColor,
                      ),
                    ),
                  ),
                  isExpanded: true,
                  items: itemsList.map<DropdownMenuItem<String>>((value) {
                    return DropdownMenuItem<String>(
                      value: value,
                      child: Text(
                        value.toString(),
                        style: CustomStyle.lightHeading3TextStyle,
                      ),
                    );
                  }).toList(),
                  onChanged: onChanged,
                  value:
                      selectMethod.value.isNotEmpty ? selectMethod.value : null,
                ),
              ),
            ),
          ),
        ));
  }
}
