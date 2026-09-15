import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:get/get.dart';
import 'package:goldinvest/widgets/common/text_labels/title_sub_title_widget.dart';

import '../../../utils/dimensions.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/size.dart';
import '../../widgets/common/buttons/primary_button.dart';

class CongratulationScreen extends StatelessWidget {
  const CongratulationScreen({
    super.key,
    required this.subTitleString,
    required this.route,
  });
  final String subTitleString;

  final String route;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _bodyWidget(context),
    );
  }

  Widget _bodyWidget(BuildContext context) {
    return SizedBox(
      height: MediaQuery.of(context).size.height,
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: crossCenter,
        children: [
          _congratulationImageWidget(context),
          verticalSpace(Dimensions.heightSize * 2),
          _congratulationInfoWidget(context),
          verticalSpace(Dimensions.heightSize * 1.33),
          _buttonWidget(context),
        ],
      ),
    );
  }

  Widget _buttonWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.symmetric(horizontal: Dimensions.marginSizeHorizontal),
      child: PrimaryButton(
        title: Strings.loginNow,
        buttonTextColor: CustomColor.whiteColor,
        onPressed: () {
          Get.toNamed(
            route,
          );
        },
      ),
    );
  }

  Widget _congratulationImageWidget(BuildContext context) {
    return SvgPicture.asset(
      Assets.icon.tickIcon,
    );
  }

  Widget _congratulationInfoWidget(BuildContext context) {
    return Container(
        alignment: Alignment.center,
        margin:
            EdgeInsets.symmetric(horizontal: Dimensions.marginSizeHorizontal),
        child: Column(
          crossAxisAlignment: crossCenter,
          children: [
            TitleSubTitleWidget(
              isCenterText: true,
              title: Strings.congratulations,
              subTitle: subTitleString,
            ),
          ],
        ));
  }
}
