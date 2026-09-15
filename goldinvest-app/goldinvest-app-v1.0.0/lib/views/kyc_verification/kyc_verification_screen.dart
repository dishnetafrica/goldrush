import 'package:goldinvest/routes/routes.dart';
import 'package:goldinvest/utils/responsive_layout.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/utils/size.dart';

import '../../backend/utils/custom_loading_api.dart';
import '../../controller/kyc/kyc_controller.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../widgets/common/app_bar/back_button.dart';
import '../../widgets/common/app_bar/primary_app_bar.dart';
import '../../widgets/common/buttons/primary_button.dart';
import '../../widgets/kyc/kyc_heading.dart';
import '../../widgets/kyc/kyc_input_widget.dart';
import '../../widgets/kyc/kyc_status_widget.dart';
part 'kyc_verification_mobile_screen.dart';

class KycVerificationScreen extends StatelessWidget {
  const KycVerificationScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(mobileScaffold: KycVerificationMobileScreen());
  }
}
