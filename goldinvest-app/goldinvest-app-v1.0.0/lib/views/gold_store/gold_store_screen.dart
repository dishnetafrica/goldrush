import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:goldinvest/controller/gold/gold_store_controller.dart';
import 'package:goldinvest/utils/responsive_layout.dart';
import 'package:get/get.dart';
import 'package:goldinvest/utils/dimensions.dart';
import 'package:goldinvest/utils/size.dart';
import 'package:goldinvest/widgets/card_info/cardinfo.dart';
import 'package:goldinvest/widgets/common/text_labels/title_heading2_widget.dart';
import 'package:shimmer/shimmer.dart';

import '../../backend/model/gold_store/gold_store_model.dart';
import '../../backend/utils/custom_loading_api.dart';
import '../../languages/strings.dart';
import '../../routes/routes.dart';
import '../../utils/custom_color.dart';
import '../../widgets/common/buttons/primary_button.dart';
import '../../widgets/common/text_labels/title_heading5_widget.dart';
part 'gold_store_mobile_screen.dart';

class GoldStoreScreen extends StatelessWidget {
  const GoldStoreScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(mobileScaffold: GoldStoreMobileScreen());
  }
}
