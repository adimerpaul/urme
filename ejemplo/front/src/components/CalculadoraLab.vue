<template>
  <q-card class="calc-panel">
    <!-- HEADER -->
    <q-card-section class="row items-center q-pb-sm">
      <div>
        <div class="text-subtitle1 text-weight-bold">Calculadora de Laboratorio</div>
<!--        <div class="text-caption text-grey-7">Chagas · Clamidia · ENA</div>-->
      </div>
      <q-space />
      <q-btn flat round dense icon="close" @click="$emit('close')" />
    </q-card-section>

    <q-separator />

    <!-- BODY -->
    <q-card-section class="q-pa-sm">

      <q-tabs v-model="tab" dense class="text-primary" active-color="primary" indicator-color="primary" align="justify">
        <q-tab name="chagas" label="Chagas" no-caps/>
        <q-tab name="clamidia" label="Clamidia" no-caps/>
        <q-tab name="ena" label="ENA" no-caps/>
        <q-tab name="toxo" label="Toxo" no-caps/>
        <q-tab name="cmv" label="CMV Lg G" no-caps />
      </q-tabs>

      <q-separator class="q-mt-sm q-mb-md" />

      <q-tab-panels v-model="tab" animated>

        <!-- ========================= CHAGAS ========================= -->
        <q-tab-panel name="chagas" class="q-pa-none">
          <div class="text-subtitle2 text-weight-bold q-mb-sm">Chagas</div>

          <div class="row q-col-gutter-sm">
            <div class="col-12 col-sm-6">
              <q-input
                v-model="chagas.posi"
                dense outlined
                label="Positivo"
                hint="Ej: 1529"
              />
            </div>
            <div class="col-12"></div>
            <div class="col-12 col-sm-6">
              <q-input
                v-model="chagas.neg1"
                dense outlined
                label="Negativo 1"
                hint="Acepta coma o punto (ej: 0,07)"
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-input
                v-model="chagas.neg2"
                dense outlined
                label="Negativo 2"
                hint="Acepta coma o punto (ej: 0,1)"
              />
            </div>
          </div>

          <q-card flat bordered class="q-mt-sm">
            <q-card-section class="q-pa-sm">
              <div class="row q-col-gutter-sm">
                <div class="col-12 col-sm-4">
                  <div class="text-caption text-grey-7">(neg1 + 0.1)</div>
                  <div class="text-body1 text-weight-bold">{{ fmt(chagasSum) }}</div>
                </div>
                <div class="col-12 col-sm-4">
                  <div class="text-caption text-grey-7">(neg1 + 10%)</div>
                  <div class="text-body1 text-weight-bold">{{ fmt(chagasPositivo) }}</div>
                </div>
                <div class="col-12 col-sm-4">
                  <div class="text-caption text-grey-7">(neg1 - 10%)</div>
                  <div class="text-body1 text-weight-bold">{{ fmt(chagasControl2) }}</div>
                </div>
              </div>
            </q-card-section>
          </q-card>

<!--          <div class="q-mt-sm text-caption text-grey-7">-->
<!--            Fórmulas: <b>suma=neg1+neg2</b>, <b>positivo=suma*1.1</b>, <b>control2=suma*0.9</b>-->
<!--          </div>-->
        </q-tab-panel>

        <!-- ========================= CLAMIDIA ========================= -->
        <q-tab-panel name="clamidia" class="q-pa-none">
          <div class="text-subtitle2 text-weight-bold q-mb-sm">Clamidia</div>

          <div class="row q-col-gutter-sm">
            <div class="col-12 col-sm-6">
              <q-input
                v-model="clamidia.cof"
                dense outlined
                label="COF"
                hint="Ej: 0,65"
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-input
                v-model="clamidia.posi"
                dense outlined
                label="Positivo (valor)"
                hint="Ej: 2,43"
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-input
                v-model="clamidia.nega"
                dense outlined
                label="Negativo (valor)"
                hint="Ej: 0,643"
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-input
                v-model="clamidia.sus"
                dense outlined
                label="6 sus (valor)"
                hint="Ej: 3,343"
              />
            </div>
          </div>

          <q-card flat bordered class="q-mt-sm">
            <q-card-section class="q-pa-sm">
              <div class="text-caption text-grey-7">Índice (sus / cof)</div>
              <div class="text-h6 text-weight-bold">{{ fmt(clamidiaIndice*10) }}</div>
              <div class="text-caption text-grey-7 q-mt-xs">
                Fórmula: <b>indice = sus / cof</b>
              </div>
            </q-card-section>
          </q-card>
        </q-tab-panel>

        <!-- ========================= ENA ========================= -->
        <q-tab-panel name="ena" class="q-pa-none">
          <div class="text-subtitle2 text-weight-bold q-mb-sm">ENA</div>

          <div class="row q-col-gutter-sm">
            <div class="col-12 col-sm-4">
              <q-input
                v-model="ena.blanco"
                dense outlined
                label="Blanco"
                hint="Ej: 0,003"
              />
            </div>

            <div class="col-12 col-sm-4">
              <q-input
                v-model="ena.cal_conc"
                dense outlined
                label="Calibrador (concentración)"
                hint="Ej: 1,18"
              />
            </div>

            <div class="col-12 col-sm-4">
              <q-input
                v-model="ena.cal_factor"
                dense outlined
                label="Factor"
                hint="Ej: 0,2"
              />
            </div>
          </div>

          <q-card flat bordered class="q-mt-sm">
            <q-card-section class="q-pa-sm">
              <div class="row items-center q-col-gutter-sm">
                <div class="col-12 col-sm-6">
                  <div class="text-caption text-grey-7">Calibrador (conc * factor)</div>
                  <div class="text-h6 text-weight-bold">{{ fmt(enaCalibrador) }}</div>
                </div>
                <div class="col-12 col-sm-6 text-caption text-grey-7">
                  Fórmula: <b>calibrador = cal_conc * cal_factor</b>
                </div>
              </div>
            </q-card-section>
          </q-card>

          <div class="q-mt-md text-subtitle2 text-weight-bold">Diluciones</div>

          <div class="row q-col-gutter-sm q-mt-xs">
            <div class="col-12 col-sm-4" v-for="k in ['d1','d2','d3','d4','d5','d6']" :key="k">
              <q-input
                v-model="ena[k]"
                dense outlined
                :label="k.toUpperCase()"
                hint="Ej: 0,056"
              />
            </div>
          </div>

          <q-card flat bordered class="q-mt-sm">
            <q-card-section class="q-pa-sm">
              <div class="text-caption text-grey-7 q-mb-sm">
                Cálculo: <b>d / calibrador</b>
              </div>

              <div class="row q-col-gutter-sm">
                <div class="col-6 col-sm-4" v-for="k in ['d1','d2','d3','d4','d5','d6']" :key="'r-'+k">
                  <div class="text-caption text-grey-7">{{ k.toUpperCase() }}</div>
                  <div class="text-body1 text-weight-bold">{{ fmt(enaRatio(k)) }}</div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </q-tab-panel>
        <!-- ========================= TOXO ========================= -->
        <q-tab-panel name="toxo" class="q-pa-none">
          <div class="text-subtitle2 text-weight-bold q-mb-sm">Toxo</div>
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-sm-6">
              <q-input
                v-model="toxo.cof"
                dense outlined
                label="COF"
                hint="Ej: 0,65"
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-input
                v-model="toxo.posi"
                dense outlined
                label="Positivo (valor)"
                hint="Ej: 2,43"
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-input
                v-model="toxo.nega"
                dense outlined
                label="Negativo (valor)"
                hint="Ej: 0,643"
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-input
                v-model="toxo.sus"
                dense outlined
                label="6 sus (valor)"
                hint="Ej: 3,343"
              />
            </div>
<!--            el resulado es sus/sobre cof-->
            <div class="col-12 col-sm-12 q-mt-md">
              <q-card flat bordered class="q-mt-sm">
                <q-card-section class="q-pa-sm">
                  <div class="text-caption text-grey-7">Índice (sus / cof)*10</div>
                  <div class="text-h6 text-weight-bold">{{ fmt(toxo.sus / toxo.cof * 10) }}</div>
                  <div class="text-caption text-grey-7 q-mt-xs">
                    Fórmula: <b>indice = sus / cof</b>
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </div>
        </q-tab-panel>
<!--        cmv-->
        <q-tab-panel name="cmv" class="q-pa-none">
          <div class="text-subtitle2 text-weight-bold q-mb-sm">CMV Lg G</div>
          <div class="text-body2">
<!--            CAL	20		VALOR EQUIPO	28,1		0,711743772-->
<!--            POS-->
<!--            NEG-->
<!--            VALOR PAC	103,5		73,66548043-->
<!--            100,8		71,74377224-->
<!--            115		81,85053381-->
<!--            81		57,65124555-->
<!--            49,8		35,44483986-->
<!--            cmv: {-->
<!--            cal: '20',-->
<!--            valorEquipo: '28.1',-->
<!--            valorPac1: '103.5',-->
<!--            valorPac2: '100.8',-->
<!--            valorPac3: '115',-->
<!--            valorPac4: '81',-->
<!--            valorPac5: '49.8'-->
<!--            }-->
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-md-4">
                <q-input
                  v-model="cmv.cal"
                  dense outlined
                  label="CAL"
                  hint="Ej: 20"
                />
              </div>
              <div class="col-12 col-md-4">
                <q-input
                  v-model="cmv.valorEquipo"
                  dense outlined
                  label="Valor Equipo"
                  hint="Ej: 28,1"
                />
              </div>
              <div class="col-12 col-md-4">
<!--                CAL	*		VALOR EQUIPO-->
<!--                <label class="text-subtitle2"  >CAL * Valor Equipo</label>-->
                <div class="text-caption text-grey-7">CAL / Valor Equipo</div>
                <div class="text-body1 text-weight-bold">{{ fmt( (cmv.cal / cmv.valorEquipo) ) }}</div>
              </div>
              <div class="col-12 col-md-4 q-mt-md">
                <q-input
                  v-model="cmv.valorPac1"
                  dense outlined
                  label="Valor Pac 1"
                  hint="Ej: 103,5"
                />
              </div>
              <div class="col-12 col-md-4 q-mt-md">
                <q-input
                  v-model="cmv.valorPac2"
                  dense outlined
                  label="Valor Pac 2"
                  hint="Ej: 100,8"
                />
              </div>
              <div class="col-12 col-md-4 q-mt-md">
                <q-input
                  v-model="cmv.valorPac3"
                  dense outlined
                  label="Valor Pac 3"
                  hint="Ej: 115"
                />
              </div>
              <div class="col-12 col-md-4 q-mt-md">
                <q-input
                  v-model="cmv.valorPac4"
                  dense outlined
                  label="Valor Pac 4"
                  hint="Ej: 81"
                />
              </div>
              <div class="col-12 col-md-4 q-mt-md">
                <q-input
                  v-model="cmv.valorPac5"
                  dense outlined
                  label="Valor Pac 5"
                  hint="Ej: 49,8"
                />
              </div>
              <div class="col-12 col-md-4 q-mt-md">
                <q-input
                  v-model="cmv.valorPac6"
                  dense outlined
                  label="Valor Pac 6"
                  hint="Ej: 0"
                />
              </div>
              <q-card flat bordered class="q-mt-sm">
                <q-card-section class="q-pa-sm">
                  <div class="text-caption text-grey-7 q-mb-sm">
                    Cálculo: <b>valor / (CAL / Valor Equipo)</b>
                  </div>

                  <div class="row q-col-gutter-sm">
                    <div class="col-6 col-sm-4" v-for="k in ['1','2','3','4','5','6']" :key="'r-'+k">
                      <div class="text-caption text-grey-7">
                        Valor Pac {{k}}
                      </div>
                      <div class="text-body1 text-weight-bold">
                        {{ (cmv['valorPac'+k] * (cmv.cal / cmv.valorEquipo)).toFixed(2) }}
<!--                        {{ fmt(enaRatio(k)) }}-->
                      </div>
                    </div>
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </div>
        </q-tab-panel>

      </q-tab-panels>

      <q-separator class="q-my-md" />

      <!-- FOOTER -->
      <div class="row items-center q-gutter-sm">
        <q-btn outline no-caps icon="content_copy" label="Copiar resultados" @click="copyResults" />
        <q-space />
        <q-btn color="primary" no-caps icon="close" label="Cerrar" @click="$emit('close')" />
      </div>

    </q-card-section>
  </q-card>
</template>

<script>
export default {
  name: 'CalculadoraLab',
  emits: ['close'],
  data () {
    return {
      tab: 'chagas',

      chagas: {
        posi: '1529',
        neg1: '0.07',
        neg2: '0.002'
      },

      clamidia: {
        cof: '0.65',
        sus: '3.343',
        posi: '2.43',
        nega: '0.643'
      },

      ena: {
        blanco: '0.003',
        cal_conc: '1.18',
        cal_factor: '0.2',
        d1: '0.056',
        d2: '0.035',
        d3: '0.011',
        d4: '0.026',
        d5: '0.038',
        d6: '0.031'
      },
      toxo: {
        cof: '0.788',
        posi: '1.966',
        nega: '0.482',
        sus: '2.193'
      },
      cmv: {
        cal: '20',
        valorEquipo: '28.1',
        valorPac1: '103.5',
        valorPac2: '100.8',
        valorPac3: '115',
        valorPac4: '81',
        valorPac5: '49.8',
        valorPac6: '0'
      }
    }
  },

  computed: {
    // ---- CHAGAS
    chagasSum () {
      return this.n(this.chagas.neg1) + 0.1
    },
    chagasPositivo () {
      return this.chagasSum * 1.1
    },
    chagasControl2 () {
      return this.chagasSum * 0.9
    },

    // ---- CLAMIDIA
    clamidiaIndice () {
      const cof = this.n(this.clamidia.cof)
      const sus = this.n(this.clamidia.sus)
      if (!cof) return 0
      return sus / cof
    },

    // ---- ENA
    enaCalibrador () {
      return this.n(this.ena.cal_conc) * this.n(this.ena.cal_factor)
    }
  },

  methods: {
    // convierte "0,07" -> 0.07
    n (v) {
      if (v === null || v === undefined) return 0
      const s = String(v).trim().replace(/\s+/g, '').replace(',', '.')
      const num = parseFloat(s)
      return isNaN(num) ? 0 : num
    },

    // formatea bonito
    fmt (num) {
      const n = Number(num || 0)
      // si es muy pequeño o muchos decimales: 6; sino 3
      const dec = Math.abs(n) < 1 ? 6 : 2
      // return n.toFixed(dec).replace('.', ',') derondear a 2 decimales
      return n.toFixed(dec).replace('.', ',')
    },

    enaRatio (k) {
      const cal = this.enaCalibrador
      if (!cal) return 0
      return this.n(this.ena[k]) / cal
    },

    async copyResults () {
      const text =
        `CHAGAS\n` +
        `suma: ${this.fmt(this.chagasSum)}\n` +
        `positivo (x1,1): ${this.fmt(this.chagasPositivo)}\n` +
        `control2 (x0,9): ${this.fmt(this.chagasControl2)}\n\n` +
        `CLAMIDIA\n` +
        `indice (sus/cof): ${this.fmt(this.clamidiaIndice)}\n\n` +
        `ENA\n` +
        `calibrador (conc*factor): ${this.fmt(this.enaCalibrador)}\n` +
        `d1/cal: ${this.fmt(this.enaRatio('d1'))}\n` +
        `d2/cal: ${this.fmt(this.enaRatio('d2'))}\n` +
        `d3/cal: ${this.fmt(this.enaRatio('d3'))}\n` +
        `d4/cal: ${this.fmt(this.enaRatio('d4'))}\n` +
        `d5/cal: ${this.fmt(this.enaRatio('d5'))}\n` +
        `d6/cal: ${this.fmt(this.enaRatio('d6'))}\n`

      try {
        await navigator.clipboard.writeText(text)
        this.$q.notify({ type: 'positive', message: 'Resultados copiados' })
      } catch (e) {
        console.error(e)
        this.$q.notify({ type: 'negative', message: 'No se pudo copiar (permiso del navegador)' })
      }
    }
  }
}
</script>

<style scoped>
.calc-panel{
  width: 380px;
  max-width: 92vw;
  height: 100vh;
  border-radius: 0;
}
</style>
