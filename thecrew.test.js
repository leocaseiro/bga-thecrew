require([
    // "dojo",
    // "dojo/_base/declare",
    // "ebg/core/gamegui",
    // "ebg/counter",
    "./thecrew.js"
    // "ebg/stock",
    // "ebg/counter",
    // "modules/js/Game/game.js",
    // "modules/js/CardTrait.js"
], (Crew) => {
    QUnit.module("Card", {
        setup: function (assert) {

        },
        teardown: function (assert) {

        }
    });

    QUnit.test("Test cardTrait", function(assert) {
        // debugger;
        var RESULT = thecrew.cardTrait.prototype.getCardUniqueType(2,2);

        assert.equal(RESULT, 11, 'result 11');

        // Alternative
        assert.equal(Crew.prototype.getCardUniqueType(2, 2), 11,
            "getCardUniqueType should return 11");
    });

    QUnit.test("Check if setup calls addCardInHand", function(assert) {
        // next step is setup sinon to spy / mock
        thecrew.cardTrait.prototype.setupHand([]);
        // var RESULT = thecrew.cardTrait.prototype.addCardInHand();

        assert.equal(RESULT, 11, 'result 11');

        // assert.equal(Crew.prototype.getCardUniqueType(2, 2), 11,
        //     "getCardUniqueType should return 11");
    });
});
