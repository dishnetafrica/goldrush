import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';

import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';

class CustomTabWidget extends StatelessWidget {
  final String title;

  const CustomTabWidget({
    super.key,
    required this.title,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: CustomColor.blackColor.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(Dimensions.radius * 0.8),
      ),
      child: FittedBox(
        child: Text(
          DynamicLanguage.isLoading ? "" : DynamicLanguage.key(title),
          style: TextStyle(
              fontSize: Dimensions.headingTextSize3 * 0.9,
              fontWeight: FontWeight.w500),
        ),
      ),
    );
  }
}
