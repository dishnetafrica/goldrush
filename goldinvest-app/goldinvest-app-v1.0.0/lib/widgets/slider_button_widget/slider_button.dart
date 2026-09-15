import 'package:action_slider/action_slider.dart';
import 'package:flutter/material.dart';
import 'package:goldinvest/utils/size.dart';
import 'package:goldinvest/widgets/common/others/custom_image_widget.dart';
import 'package:goldinvest/widgets/common/text_labels/title_heading3_widget.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/dimensions.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';

class SlideButtonWidget extends StatelessWidget {
  SlideButtonWidget({
    super.key,
    required this.action,
    required this.title,
  });
  final _controller = ActionSliderController();
  final VoidCallback action;
  final String title;
  @override
  Widget build(BuildContext context) {
    return ActionSlider.custom(
      sliderBehavior: SliderBehavior.stretch,
      controller: _controller,
      toggleMargin: EdgeInsets.all(Dimensions.paddingSize * 0.22),
      backgroundColor: Colors.green,
      foregroundChild: DecoratedBox(
        decoration: BoxDecoration(
          color: Theme.of(context).primaryColor,
          borderRadius: BorderRadius.circular(Dimensions.radius),
        ),
        child: Padding(
          padding: EdgeInsets.all(Dimensions.paddingSize * 0.3),
          child: CustomImageWidget(
            path: Assets.icon.arrowRight,
            color: CustomColor.whiteColor,
          ),
        ),
      ),
      foregroundBuilder: (context, state, child) => child!,
      outerBackgroundBuilder: (context, state, child) => Card(
        margin: EdgeInsets.zero,
        elevation: 0,
        color: Color.lerp(
          Theme.of(context).colorScheme.surface,
          Theme.of(context).colorScheme.surface,
          state.position,
        ),
        child: Center(
          child: Row(
            mainAxisSize: mainMin,
            children: [
              const TitleHeading3Widget(
                text: Strings.swipeTo,
              ),
              horizontalSpace(Dimensions.widthSize * 0.2),
              TitleHeading3Widget(
                text: title,
              ),
            ],
          ),
        ),
      ),
      backgroundBorderRadius: BorderRadius.circular(Dimensions.radius),
      action: (controller) async {
        action();
      },
    );
  }
}
