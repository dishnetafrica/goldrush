// ignore_for_file: public_member_api_docs, sort_constructors_first
import 'package:dropdown_button2/dropdown_button2.dart';
import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../utils/custom_color.dart';
import '../utils/dimensions.dart';
import '../widgets/common/text_labels/title_heading3_widget.dart';

class ChangeLanguageWidget extends StatelessWidget {
  final Color? dropMenuColor;
  final Color? dorpButtonColor;
  final Color? dropTextColor;
  final Color? arrowColor;

  const ChangeLanguageWidget({
    super.key,
    this.dropMenuColor,
    this.dorpButtonColor,
    this.dropTextColor,
    this.arrowColor,
    this.isOnboard = false,
    required this.routeOnChange,
  });
  final bool isOnboard;
  final String routeOnChange;

  @override
  Widget build(BuildContext context) {
    return Obx(
      () => !isOnboard
          ? _dropDown(context)
          : Container(
              alignment: Alignment.center,
              padding: EdgeInsets.symmetric(
                vertical: Dimensions.paddingSize * 0,
                horizontal: Dimensions.paddingSize * 0.05,
              ),
              decoration: BoxDecoration(
                color: CustomColor.primaryLightColor.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(Dimensions.radius * 0.6),
              ),
              child: _dropDown(context),
            ),
    );
  }

  Directionality _dropDown(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.ltr,
      child: Container(
        margin: EdgeInsets.symmetric(horizontal: Dimensions.paddingSize * .8),
        decoration: BoxDecoration(
            color: dorpButtonColor ?? CustomColor.primaryLightColor,
            borderRadius: BorderRadius.circular(Dimensions.radius)),
        width: Dimensions.widthSize * 12,
        child: DropdownButton2<String>(
          isDense: false,
          isExpanded: true,
          iconStyleData: IconStyleData(
            icon: Padding(
              padding: const EdgeInsets.only(right: 4),
              child: Icon(
                Icons.arrow_drop_down_rounded,
                color: arrowColor ?? CustomColor.whiteColor,
              ),
            ),
          ),
          dropdownStyleData: DropdownStyleData(
            maxHeight: MediaQuery.sizeOf(context).height * .26,
            decoration: BoxDecoration(
              color: dropMenuColor ?? CustomColor.primaryLightColor,
              borderRadius: BorderRadius.circular(Dimensions.radius),
            ),
          ),
          value: DynamicLanguage.selectedLanguage.value,
          underline: Container(),
          onChanged: (String? newValue) {
            if (newValue != null) {
              DynamicLanguage.changeLanguage(newValue);
              Get.offAllNamed(routeOnChange);
            }
          },
          items: DynamicLanguage.languages.map<DropdownMenuItem<String>>(
            (language) {
              return DropdownMenuItem<String>(
                value: language.code,
                child: TitleHeading3Widget(
                  text: isOnboard ? language.code.toUpperCase() : language.name,
                  color: dropTextColor ?? CustomColor.whiteColor,
                ),
              );
            },
          ).toList(),
        ),
      ),
    );
  }
}
