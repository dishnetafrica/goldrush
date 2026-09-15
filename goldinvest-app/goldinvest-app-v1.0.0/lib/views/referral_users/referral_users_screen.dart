import 'package:flutter/material.dart';

import '../../utils/responsive_layout.dart';
import 'package:goldinvest/widgets/common/app_bar/primary_app_bar.dart';

import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../widgets/common/others/custom_image_widget.dart';
import '../../widgets/common/text_labels/title_heading4_widget.dart';
part 'referral_users_mobile_screen.dart';

class ReferralUsers extends StatelessWidget {
  const ReferralUsers({super.key});

  @override
  Widget build(BuildContext context) {
    return const ResponsiveLayout(mobileScaffold: ReferralUsersMobileScreen());
  }
}
