import 'dart:ui';

import 'package:flutter/material.dart';

import '../../custom_assets/assets.gen.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../common/others/custom_image_widget.dart';

class SlideToActButtonController {
  static Function? resetSlider;
}

class SlideToActButton extends StatefulWidget {
  final Function onSlideComplete;
  final String title;

  const SlideToActButton({
    required this.onSlideComplete,
    required this.title,
    super.key,
  });

  @override
  // ignore: library_private_types_in_public_api
  _SlideToActButtonState createState() => _SlideToActButtonState();
}

class _SlideToActButtonState extends State<SlideToActButton>
    with SingleTickerProviderStateMixin {
  late AnimationController _animationController;
  double _dragPosition = 0.0;
  bool _isCompleted = false;

  @override
  void initState() {
    super.initState();
    _animationController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 500),
    );

    // Assign the reset function to the static controller
    SlideToActButtonController.resetSlider = _resetSlider;
  }

  void _onDragUpdate(DragUpdateDetails details) {
    setState(() {
      _dragPosition += details.primaryDelta! / context.size!.width;
      if (_dragPosition >= 0.8) {
        _dragPosition = 0.8;
        _onSlideComplete();
      } else if (_dragPosition <= 0.0) {
        _dragPosition = 0.0;
      }
    });
  }

  void _onDragEnd(DragEndDetails details) {
    if (!_isCompleted) {
      setState(() {
        _dragPosition = 0.0;
      });
    }
  }

  void _onSlideComplete() {
    if (!_isCompleted) {
      _isCompleted = true;
      widget.onSlideComplete();
      _animationController.forward();
    }
  }

  void _resetSlider() {
    setState(() {
      _dragPosition = 0.0;
      _isCompleted = true;
      _animationController.reset();
    });
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onHorizontalDragUpdate: _onDragUpdate,
      onHorizontalDragEnd: _onDragEnd,
      child: Stack(
        children: [
          BackdropFilter(
            filter: ImageFilter.blur(
              sigmaX:
                  _dragPosition * 20, // Blur effect depends on drag position
              sigmaY: _dragPosition * 10,
            ),
          ),
          Container(
            height: Dimensions.heightSize * 4.67,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(Dimensions.radius * 1.2),
              color: CustomColor.primaryLightColor.withValues(alpha: 0.1),
            ),
            child: Center(
              child: Text(
                _isCompleted ? 'Completed' : widget.title,
                style: const TextStyle(
                  color: CustomColor.primaryLightColor,
                  fontSize: 16,
                ),
              ),
            ),
          ),
          Padding(
            padding: EdgeInsets.only(
              left: Dimensions.heightSize * 0.3,
              top: Dimensions.heightSize * 0.3,
              bottom: Dimensions.heightSize * 0.3,
            ),
            child: Align(
              alignment: Alignment(_dragPosition * 2 - 1, 0),
              child: Container(
                width: Dimensions.widthSize * 4.8,
                height: Dimensions.heightSize * 4,
                decoration: BoxDecoration(
                  shape: BoxShape.rectangle,
                  borderRadius: BorderRadius.circular(Dimensions.radius * 1.2),
                  color: CustomColor.primaryLightColor,
                ),
                child: CustomImageWidget(
                  path: Assets.icon.arrowRight,
                  height: Dimensions.heightSize * 1.5,
                  width: Dimensions.widthSize * 1.4,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  void dispose() {
    _animationController.dispose();
    super.dispose();
  }
}
