// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/* global STROKEWEIGHT, ANNOTATIONCOLOUR */

/**
 * Provides an in browser PDF editor.
 *
 * @module moodle-fastassignfeedback_editpdf-editor
 */

/**
 * Class representing a rectangle.
 *
 * @namespace M.fastassignfeedback_editpdf
 * @class annotationrectangle
 * @extends M.fastassignfeedback_editpdf.annotation
 */
var ANNOTATIONRECTANGLE = function(config) {
    ANNOTATIONRECTANGLE.superclass.constructor.apply(this, [config]);
};

ANNOTATIONRECTANGLE.NAME = "annotationrectangle";
ANNOTATIONRECTANGLE.ATTRS = {};

Y.extend(ANNOTATIONRECTANGLE, M.fastassignfeedback_editpdf.annotation, {
    /**
     * Draw a rectangle annotation
     * @protected
     * @method draw
     * @return M.fastassignfeedback_editpdf.drawable
     */
    draw: function() {
        var drawable,
            bounds,
            shape;

        drawable = new M.fastassignfeedback_editpdf.drawable(this.editor);

        bounds = new M.fastassignfeedback_editpdf.rect();
        bounds.bound([new M.fastassignfeedback_editpdf.point(this.x, this.y),
                      new M.fastassignfeedback_editpdf.point(this.endx, this.endy)]);

        shape = this.editor.graphic.addShape({
            type: Y.Rect,
            width: bounds.width,
            height: bounds.height,
            stroke: {
               weight: STROKEWEIGHT,
               color: ANNOTATIONCOLOUR[this.colour]
            },
            x: bounds.x,
            y: bounds.y
        });
        drawable.shapes.push(shape);
        this.drawable = drawable;

        return ANNOTATIONRECTANGLE.superclass.draw.apply(this);
    },

    /**
     * Draw the in progress edit.
     *
     * @public
     * @method draw_current_edit
     * @param M.fastassignfeedback_editpdf.edit edit
     */
    draw_current_edit: function(edit) {
        var drawable = new M.fastassignfeedback_editpdf.drawable(this.editor),
            shape,
            bounds;

        bounds = new M.fastassignfeedback_editpdf.rect();
        bounds.bound([new M.fastassignfeedback_editpdf.point(edit.start.x, edit.start.y),
                      new M.fastassignfeedback_editpdf.point(edit.end.x, edit.end.y)]);

        // Set min. width and height of rectangle.
        if (!bounds.has_min_width()) {
            bounds.set_min_width();
        }
        if (!bounds.has_min_height()) {
            bounds.set_min_height();
        }

        shape = this.editor.graphic.addShape({
            type: Y.Rect,
            width: bounds.width,
            height: bounds.height,
            stroke: {
               weight: STROKEWEIGHT,
               color: ANNOTATIONCOLOUR[edit.annotationcolour]
            },
            x: bounds.x,
            y: bounds.y
        });

        drawable.shapes.push(shape);

        return drawable;
    }
});

M.fastassignfeedback_editpdf = M.fastassignfeedback_editpdf || {};
M.fastassignfeedback_editpdf.annotationrectangle = ANNOTATIONRECTANGLE;
